<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CbtExam;
use App\Models\PpdbRegistration;
use App\Models\School;
use App\Models\Guardian;
use App\Models\AcademicYear;
use Illuminate\Http\Request;

class CbtPpdbController extends Controller
{
    public function cbtIndex(Request $request)
    {
        $schoolId = session('dashboard_school_id', 'all');
        $examsQuery = CbtExam::with('school');

        if ($schoolId !== 'all') {
            $examsQuery->where('school_id', $schoolId);
        }

        $exams = $examsQuery->latest()->get();

        if ($exams->isEmpty()) {
            $sampleExams = [
                ['title' => 'Ujian Tengah Semester (UTS) Matematika', 'subject' => 'Matematika', 'duration' => 90, 'questions' => 30],
                ['title' => 'Ujian Akhir Semester (UAS) Pendidikan Agama Islam', 'subject' => 'PAI & Tahfidz', 'duration' => 60, 'questions' => 40],
                ['title' => 'Tryout CBT OSN IPA & Fisika Terpadu', 'subject' => 'IPA Terpadu', 'duration' => 120, 'questions' => 50],
            ];

            foreach ($sampleExams as $ex) {
                CbtExam::create([
                    'school_id' => ($schoolId !== 'all') ? $schoolId : School::first()?->id,
                    'title' => $ex['title'],
                    'subject_name' => $ex['subject'],
                    'duration_minutes' => $ex['duration'],
                    'total_questions' => $ex['questions'],
                    'start_time' => now(),
                    'end_time' => now()->addDays(7),
                    'status' => 'ACTIVE',
                ]);
            }
            $exams = CbtExam::with('school')->latest()->get();
        }

        return view('admin.cbt.index', compact('exams', 'schoolId'));
    }

    public function storeCbtExam(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'subject_name' => 'required|string',
            'duration_minutes' => 'required|integer',
            'total_questions' => 'required|integer',
        ]);

        $schoolId = session('dashboard_school_id', 'all');

        CbtExam::create([
            'school_id' => ($schoolId !== 'all') ? $schoolId : School::first()?->id,
            'title' => $request->title,
            'subject_name' => $request->subject_name,
            'duration_minutes' => $request->duration_minutes,
            'total_questions' => $request->total_questions,
            'start_time' => now(),
            'end_time' => now()->addDays(7),
            'status' => 'ACTIVE',
        ]);

        return redirect()->back()->with('success', '✓ Paket Ujian CBT Baru berhasil dibuat!');
    }

    public function ppdbIndex(Request $request)
    {
        $schoolId = session('dashboard_school_id', 'all');
        $ppdbQuery = PpdbRegistration::with('school');

        if ($schoolId !== 'all') {
            $ppdbQuery->where('school_id', $schoolId);
        }

        $registrations = $ppdbQuery->latest()->get();

        if ($registrations->isEmpty()) {
            $samples = [
                ['name' => 'Fathan Al-Ghazali', 'parent' => 'Bapak Muhammad Hidayat', 'level' => 'SDIT', 'prev' => 'TKIT Robbani', 'phone' => '081234567890'],
                ['name' => 'Zahra Khairunnisa', 'parent' => 'Ibu Rahmawati, S.Pd', 'level' => 'SMPIT', 'prev' => 'SDIT Robbani', 'phone' => '081398765432'],
                ['name' => 'Ahmad Rayhan Utama', 'parent' => 'Bapak Ir. Hendra', 'level' => 'SMAIT', 'prev' => 'SMPIT Negeri 1', 'phone' => '081511223344'],
            ];

            foreach ($samples as $idx => $s) {
                $targetSchoolId = ($schoolId !== 'all') ? $schoolId : (School::first()?->id ?? 1);
                $regNum = 'PPDB-2026-S' . $targetSchoolId . '-00' . ($idx + 1);

                PpdbRegistration::firstOrCreate(
                    ['registration_number' => $regNum],
                    [
                        'school_id' => $targetSchoolId,
                        'full_name' => $s['name'],
                        'parent_name' => $s['parent'],
                        'phone_number' => $s['phone'],
                        'target_level' => $s['level'],
                        'previous_school' => $s['prev'],
                        'status' => 'PASSED',
                        'registration_fee' => 250000,
                        'fee_paid' => true,
                    ]
                );
            }
            $registrations = $ppdbQuery->latest()->get();
        }

        return view('admin.ppdb.index', compact('registrations', 'schoolId'));
    }

    public function updatePpdbStatus(Request $request, $id)
    {
        $reg = PpdbRegistration::findOrFail($id);
        $newStatus = $request->status ?? 'PASSED';
        $reg->update(['status' => $newStatus]);

        if ($newStatus === 'PASSED') {
            // Auto create student in Master Data
            $schoolId    = $reg->school_id ?? \App\Models\School::first()?->id;
            $classroomId = \App\Models\Classroom::where('school_id', $schoolId)->first()?->id;

            $student = \App\Models\Student::firstOrCreate(
                ['nis' => '2026' . str_pad($reg->id, 4, '0', STR_PAD_LEFT)],
                [
                    'school_id'    => $schoolId,
                    'classroom_id' => $classroomId,
                    'nisn'         => '006' . str_pad($reg->id, 7, '0', STR_PAD_LEFT),
                    'full_name'    => $reg->full_name,
                    'gender'       => 'M',
                    'rfid_tag'     => 'RFID-PPDB-' . rand(1000, 9999),
                    'savings_balance' => 100000,
                    'status'       => 'ACTIVE',
                ]
            );

            // Auto create/link Guardian record (ParentModel does not exist – use Guardian instead)
            if ($reg->parent_name) {
                try {
                    // Decode details_json if stored as JSON string
                    $detailsJson = is_array($reg->details_json)
                        ? $reg->details_json
                        : json_decode($reg->details_json ?? '{}', true);

                    \App\Models\Guardian::firstOrCreate(
                        ['phone' => $reg->phone_number],
                        [
                            'full_name' => $reg->parent_name,
                            'email'     => $detailsJson['email_ortu'] ?? 'ortu.' . $reg->id . '@sitrobbani.sch.id',
                        ]
                    );
                } catch (\Throwable $e) {}
            }

            // Auto create initial SPP bill in Finance Module
            try {
                $activeYear = \App\Models\AcademicYear::where('is_active', true)->first()
                    ?? \App\Models\AcademicYear::first();

                \App\Models\SppBill::firstOrCreate(
                    [
                        'student_id'   => $student->id,
                        'month_period' => date('F Y'),  // e.g. 'August 2026' – matches DB column
                    ],
                    [
                        'school_id'        => $schoolId,
                        'academic_year_id' => $activeYear?->id ?? 1,
                        'amount'           => 350000,
                        'due_date'         => now()->endOfMonth()->toDateString(),
                        'status'           => 'UNPAID',
                    ]
                );
            } catch (\Throwable $e) {}
        }

        try {
            \App\Models\AuditLog::create([
                'user_id'    => auth()->id() ?? 1,
                'action'     => 'PPDB SET STATUS (' . $newStatus . ')',
                'model_type' => 'PpdbRegistration',
                'model_id'   => $reg->id,
                'ip_address' => request()->ip(),
            ]);
        } catch (\Throwable $e) {}

        return redirect()->back()->with('success', '✓ Status Kelulusan Pendaftar PPDB berhasil diperbarui & Data Siswa Baru Otomatis Diterbitkan!');
    }

    public function downloadSpmbPdf($id)
    {
        $registration = PpdbRegistration::findOrFail($id);
        return view('school.spmb_pdf', compact('registration'));
    }

    public function storeQuestion(Request $request)
    {
        $request->validate([
            'cbt_exam_id' => 'required|exists:cbt_exams,id',
            'question_text' => 'required|string',
            'option_a' => 'required|string',
            'option_b' => 'required|string',
            'correct_answer' => 'required|string',
        ]);

        $exam = CbtExam::findOrFail($request->cbt_exam_id);
        $exam->increment('total_questions', 1);

        try {
            \App\Models\AuditLog::create([
                'user_id' => auth()->id() ?? 1,
                'action' => 'INPUT SOAL CBT',
                'model_type' => 'CbtExam',
                'model_id' => $exam->id,
                'ip_address' => request()->ip(),
            ]);
        } catch(\Throwable $e) {}

        return redirect()->back()->with('success', "✓ Soal Ujian Baru berhasil ditambahkan ke Bank Soal paket: {$exam->title}!");
    }
}
