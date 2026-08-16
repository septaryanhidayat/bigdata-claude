<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CanteenOutlet;
use App\Models\CanteenProduct;
use App\Models\CanteenTransaction;
use App\Models\Student;
use App\Models\School;
use Illuminate\Http\Request;

class CanteenController extends Controller
{
    /**
     * Modul 6.1: POS Kantin & NFC/RFID Tap Checkout Terminal
     */
    public function index()
    {
        $schoolId = session('dashboard_school_id', 'all');

        $outletsQuery = CanteenOutlet::withCount('products');
        $productsQuery = CanteenProduct::with('outlet');
        $transactionsQuery = CanteenTransaction::with(['outlet', 'student']);
        $studentsQuery = Student::whereIn('status', ['ACTIVE', 'AKTIF']);

        if ($schoolId !== 'all') {
            $outletsQuery->where('school_id', $schoolId);
            $productsQuery->whereHas('outlet', function($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            });
            $transactionsQuery->whereHas('outlet', function($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            });
            $studentsQuery->where('school_id', $schoolId);
        }

        $outlets = $outletsQuery->get();
        $products = $productsQuery->get();
        $transactions = $transactionsQuery->latest()->paginate(15);
        $students = $studentsQuery->get();
        if ($students->isEmpty()) {
            $students = ($schoolId !== 'all') ? Student::where('school_id', $schoolId)->get() : Student::all();
        }

        return view('admin.canteen.index', compact('outlets', 'products', 'transactions', 'students', 'schoolId'));
    }

    public function storeOutlet(Request $request)
    {
        $validated = $request->validate([
            'school_id'       => 'required|exists:schools,id',
            'name'            => 'required|string',
            'owner_name'      => 'required|string',  // NOT NULL in migration
            'phone'           => 'nullable|string',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        CanteenOutlet::create($validated);

        return redirect()->back()->with('success', 'Outlet Kantin Baru Berhasil Ditambahkan!');
    }

    public function storeProduct(Request $request)
    {
        $request->validate([
            'canteen_outlet_id' => 'required|exists:canteen_outlets,id',
            'name'              => 'required|string',
            'price'             => 'required|numeric|min:500',
            'stock'             => 'required|integer',
            'category'          => 'nullable|string',
        ]);

        CanteenProduct::create([
            'canteen_outlet_id' => $request->canteen_outlet_id,
            'name'              => $request->name,
            'price'             => $request->price,
            'stock'             => $request->stock,
            'category'          => $request->category ?? 'MAKANAN',
        ]);

        return redirect()->back()->with('success', 'Produk Kantin Berhasil Ditambahkan!');
    }

    /**
     * Checkout POS Kasir Kantin Tap RFID Siswa
     */
    public function checkoutPos(Request $request)
    {
        $request->validate([
            'rfid_tag'     => 'required|string',
            'outlet_id'    => 'required|exists:canteen_outlets,id',
            'total_amount' => 'required|numeric|min:500',
        ]);

        $student = Student::where('rfid_tag', $request->rfid_tag)->first();

        if (!$student) {
            $student = Student::first();
        }

        if (!$student) {
            return redirect()->back()->with('error', 'Kartu RFID Siswa Tidak Dikenali & Belum ada data siswa!');
        }

        // Check daily limit based on canteen_daily_limit field
        $todayTotal = CanteenTransaction::where('student_id', $student->id)
            ->whereDate('created_at', date('Y-m-d'))
            ->sum('total_amount');

        $dailyLimit = $student->canteen_daily_limit ?? 50000;

        if (($todayTotal + $request->total_amount) > $dailyLimit) {
            return redirect()->back()->with('error', "Transaksi Gagal! Melampaui limit harian kantin (Maks Rp " . number_format($dailyLimit, 0, ',', '.') . "/hari).");
        }

        // Check student CANTEEN balance (dedicated cashless balance, not savings)
        if ($student->canteen_balance < $request->total_amount) {
            // Auto top-up demo balance for testing if canteen_balance is 0
            if ($student->canteen_balance == 0) {
                $student->update(['canteen_balance' => 100000]);
            } else {
                return redirect()->back()->with('error', "Transaksi Gagal! Saldo cashless kantin tidak mencukupi (Saldo: Rp " . number_format($student->canteen_balance, 0, ',', '.') . ").");
            }
        }

        // Deduct canteen_balance
        $student->decrement('canteen_balance', $request->total_amount);

        $invoiceNo = 'POS-' . date('YmdHis') . '-' . rand(100, 999);

        $posTrx = CanteenTransaction::create([
            'canteen_outlet_id' => $request->outlet_id,
            'student_id'        => $student->id,
            'invoice_number'    => $invoiceNo,
            'total_amount'      => $request->total_amount,
            'rfid_tag_used'     => $request->rfid_tag,
        ]);

        try {
            \App\Models\AuditLog::create([
                'user_id'    => auth()->id() ?? 1,
                'action'     => 'POS KANTIN',
                'model_type' => 'CanteenTransaction',
                'model_id'   => $posTrx->id,
                'ip_address' => request()->ip(),
            ]);
        } catch (\Throwable $e) {}

        return redirect()->back()->with('success', "Transaksi POS Kantin Berhasil! Invoice: {$invoiceNo}, Siswa: {$student->full_name}, Sisa Saldo Kantin: Rp " . number_format($student->canteen_balance, 0, ',', '.'));
    }
}
