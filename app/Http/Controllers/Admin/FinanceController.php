<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SppBill;
use App\Models\SppPayment;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\Student;
use App\Models\AcademicYear;
use App\Models\School;
use Illuminate\Http\Request;

class FinanceController extends Controller
{
    /**
     * Modul 4.1: Daftar Tagihan SPP Siswa & Kasir Payment
     */
    public function sppBills()
    {
        $schoolId = session('dashboard_school_id', 'all');

        $billsQuery = SppBill::with(['student.school', 'student.classroom', 'payments']);
        $studentsQuery = Student::whereIn('status', ['ACTIVE', 'AKTIF']);

        if ($schoolId !== 'all') {
            $billsQuery->where('school_id', $schoolId);
            $studentsQuery->where('school_id', $schoolId);
        }

        $bills = $billsQuery->latest()->paginate(15);
        $students = $studentsQuery->get();
        if ($students->isEmpty()) {
            $students = ($schoolId !== 'all') ? Student::where('school_id', $schoolId)->get() : Student::all();
        }

        $academicYears = AcademicYear::orderBy('id', 'desc')->get();

        return view('admin.finance.spp_bills', compact('bills', 'students', 'schoolId', 'academicYears'));
    }

    public function storeSppBill(Request $request)
    {
        $validated = $request->validate([
            'student_id'       => 'required|exists:students,id',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'month_period'     => 'required|string',  // e.g. 'Juli 2026' – matches DB column
            'amount'           => 'required|numeric|min:0',
            'due_date'         => 'nullable|date',
        ]);

        $student = Student::find($request->student_id);
        $validated['school_id'] = $student->school_id ?? 1;
        $validated['status']    = 'UNPAID';

        // academic_year_id is required by DB FK – auto-resolve from active year if not sent
        if (empty($validated['academic_year_id'])) {
            $activeYear = AcademicYear::where('is_active', true)->first() ?? AcademicYear::first();
            $validated['academic_year_id'] = $activeYear?->id ?? 1;
        }

        // due_date is required by DB schema – default to end of month if not provided
        if (empty($validated['due_date'])) {
            $validated['due_date'] = now()->endOfMonth()->toDateString();
        }

        SppBill::create($validated);

        return redirect()->back()->with('success', 'Tagihan SPP Siswa Berhasil Dibuat!');
    }

    /**
     * Bayar Kasir SPP & Generasi Kwitansi
     */
    public function paySpp(Request $request, $billId)
    {
        $bill = SppBill::with('student')->findOrFail($billId);

        if ($bill->status == 'PAID') {
            return redirect()->back()->with('error', 'Tagihan SPP ini sudah LUNAS!');
        }

        $receiptNumber = 'KW-SPP-' . date('Ymd') . '-' . str_pad($bill->id, 4, '0', STR_PAD_LEFT);

        // SppPayment field names must match migration:
        // receipt_number, paid_at, payment_method (CASH / TRANSFER_BANK / PAYMENT_GATEWAY / TABUNGAN)
        $payment = SppPayment::create([
            'spp_bill_id'    => $bill->id,
            'receipt_number' => $receiptNumber,
            'amount_paid'    => $bill->amount - $bill->discount_amount,
            'paid_at'        => now(),
            'payment_method' => 'CASH',  // valid enum value from migration
            'notes'          => 'Pembayaran SPP via Kasir Sekolah',
        ]);

        $bill->update([
            'status'      => 'PAID',
            'paid_amount' => $bill->amount - $bill->discount_amount,
        ]);

        // Auto Record to Accounting Journal (Jurnal Otomatis)
        // account_id is the FK column name in journal_entries (not coa_id)
        $kasCoa = ChartOfAccount::where('code', '101')->first();
        if ($kasCoa) {
            JournalEntry::create([
                'school_id'        => $bill->school_id ?? 1,
                'account_id'       => $kasCoa->id,
                'date'             => now()->toDateString(),
                'reference_number' => $receiptNumber,
                'description'      => "Penerimaan SPP {$bill->month_period} - " . ($bill->student->full_name ?? 'Siswa'),
                'debit'            => $bill->amount - $bill->discount_amount,
                'credit'           => 0,
            ]);
            $kasCoa->increment('current_balance', $bill->amount - $bill->discount_amount);
        }

        try {
            \App\Models\AuditLog::create([
                'user_id'    => auth()->id() ?? 1,
                'action'     => 'BAYAR SPP',
                'model_type' => 'SppPayment',
                'model_id'   => $payment->id,
                'ip_address' => request()->ip(),
            ]);
        } catch (\Throwable $e) {}

        return redirect()->back()->with('success', "Pembayaran SPP Berhasil! Kwitansi: {$receiptNumber}");
    }

    public function printReceipt($paymentId)
    {
        $payment = SppPayment::with(['sppBill.student.school', 'sppBill.student.classroom'])->findOrFail($paymentId);
        return view('admin.finance.receipt_pdf', compact('payment'));
    }

    /**
     * Modul 4.2: Chart of Accounts (COA) & Jurnal Akuntansi
     */
    public function coa()
    {
        $coas    = ChartOfAccount::orderBy('code', 'asc')->get();
        // coa() relation on JournalEntry uses account_id → ChartOfAccount
        $journals = JournalEntry::with('coa')->latest()->paginate(15);
        $schools  = School::all();

        return view('admin.finance.coa', compact('coas', 'journals', 'schools'));
    }

    public function storeCoa(Request $request)
    {
        $validated = $request->validate([
            'school_id'       => 'required|exists:schools,id',
            'code'            => 'required|string|unique:chart_of_accounts,code',
            'name'            => 'required|string',
            'type'            => 'required|in:ASSET,LIABILITY,EQUITY,REVENUE,EXPENSE',
            'current_balance' => 'required|numeric',
        ]);

        ChartOfAccount::create($validated);

        return redirect()->back()->with('success', 'Akun COA Baru Berhasil Ditambahkan!');
    }
}
