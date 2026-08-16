<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\AcademicYear;
use App\Models\Level;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\Employee;
use App\Models\Guardian;
use App\Models\Subject;
use App\Models\Room;
use App\Models\AuditLog;
use App\Services\ImageOptimizerService;
use Illuminate\Http\Request;

class MasterDataController extends Controller
{
    /**
     * Dashboard & Ringkasan Fondasi Master Data
     */
    public function index(Request $request)
    {
        $schools = School::withCount(['classrooms', 'employees', 'students'])->get();
        $academicYears = AcademicYear::orderBy('id', 'desc')->get();
        $activeYear = AcademicYear::where('is_active', 1)->first() ?? $academicYears->first();
        
        $studentsCount  = Student::count();
        $teachersCount  = Employee::where('role_type', 'TEACHER')->count();
        $staffCount     = Employee::where('role_type', '!=', 'TEACHER')->count();
        $classroomsCount = Classroom::count();

        $activeSchoolId = session('active_school_id', $schools->first()->id ?? 1);
        $activeSchool = School::find($activeSchoolId) ?? $schools->first();

        $recentAuditLogs = AuditLog::with('user')->latest()->take(10)->get();

        return view('admin.master.index', compact(
            'schools', 'academicYears', 'activeYear', 
            'studentsCount', 'teachersCount', 'staffCount', 
            'classroomsCount', 'activeSchool', 'recentAuditLogs'
        ));
    }

    /**
     * Switch Unit Sekolah Aktif Yayasan
     */
    public function switchSchool(Request $request)
    {
        $schoolId = $request->input('school_id', 'all');
        if ($schoolId !== 'all') {
            $request->validate(['school_id' => 'exists:schools,id']);
            $school = School::find($schoolId);
            $schoolName = $school ? $school->name : 'Unit Sekolah';
        } else {
            $schoolName = 'Semua Unit (Yayasan Robbani)';
        }

        session([
            'dashboard_school_id' => $schoolId,
            'active_school_id' => $schoolId,
        ]);

        return redirect()->back()->with('success', "✓ Mode Unit Aktif Berhasil Diubah: {$schoolName}");
    }

    /**
     * Kelola Unit & Profil Sekolah (Multi-Sekolah Yayasan)
     */
    public function schools()
    {
        $schools = School::withCount(['classrooms', 'employees', 'students'])->get();
        return view('admin.master.schools', compact('schools'));
    }

    public function storeSchool(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:schools,code',
            'name' => 'required|string|max:255',
            'npsn' => 'nullable|string',
            'principal_name' => 'nullable|string',
            'address' => 'nullable|string',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'theme_color' => 'required|string',
            'logo' => 'nullable|image|max:5120',
        ]);

        if ($request->hasFile('logo')) {
            $validated['logo_url'] = ImageOptimizerService::convertAndOptimizeToWebp($request->file('logo'), 'schools');
        }

        School::create($validated);

        return redirect()->back()->with('success', 'Unit Sekolah Baru Berhasil Ditambahkan!');
    }

    public function updateSchool(Request $request, $id)
    {
        $school = School::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'npsn' => 'nullable|string',
            'principal_name' => 'nullable|string',
            'address' => 'nullable|string',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'theme_color' => 'required|string',
            'logo' => 'nullable|image|max:5120',
        ]);

        if ($request->hasFile('logo')) {
            $validated['logo_url'] = ImageOptimizerService::convertAndOptimizeToWebp($request->file('logo'), 'schools');
        }

        $school->update($validated);

        return redirect()->back()->with('success', "Data Unit Sekolah {$school->name} Berhasil Diperbarui!");
    }

    /**
     * Kelola Kurikulum & Tahun Akademik
     */
    public function curriculums()
    {
        $academicYears = AcademicYear::orderBy('id', 'desc')->get();
        return view('admin.master.curriculums', compact('academicYears'));
    }

    public function storeAcademicYear(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'semester' => 'required|string',
            'curriculum_code' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        if ($request->has('is_active')) {
            AcademicYear::query()->update(['is_active' => false]);
            $validated['is_active'] = true;
        }

        AcademicYear::create($validated);

        return redirect()->back()->with('success', 'Tahun Akademik & Kurikulum Berhasil Ditambahkan!');
    }

    /**
     * Kelola Tingkat & Rombel Kelas
     */
    public function classrooms()
    {
        $schoolId = session('dashboard_school_id', 'all');

        $classroomsQuery = Classroom::with(['school', 'level', 'homeroomTeacher']);
        $levelsQuery     = Level::query();
        if ($schoolId !== 'all') {
            $classroomsQuery->where('school_id', $schoolId);
            $levelsQuery->where('school_id', $schoolId);
        }

        $classrooms = $classroomsQuery->get();
        $schools    = School::all();
        $levels     = $levelsQuery->get();
        $teachers   = Employee::where('role_type', 'TEACHER')->get();
        if ($teachers->isEmpty()) {
            $teachers = Employee::all();
        }

        return view('admin.master.classrooms', compact('classrooms', 'schools', 'levels', 'teachers'));
    }

    public function storeClassroom(Request $request)
    {
        $validated = $request->validate([
            'school_id'           => 'required|exists:schools,id',
            'level_id'            => 'required|exists:levels,id',
            'academic_year_id'    => 'nullable|exists:academic_years,id',  // required by DB FK
            'name'                => 'required|string',
            'capacity'            => 'required|integer',
            'homeroom_teacher_id' => 'nullable|exists:employees,id',
        ]);

        // Auto-resolve active academic year if not provided
        if (empty($validated['academic_year_id'])) {
            $activeYear = AcademicYear::where('is_active', true)->first() ?? AcademicYear::first();
            $validated['academic_year_id'] = $activeYear?->id ?? 1;
        }

        Classroom::create($validated);

        return redirect()->back()->with('success', 'Rombel Kelas Berhasil Ditambahkan!');
    }

    /**
     * Kelola Data Siswa & Wali
     */
    public function students(Request $request)
    {
        $query = Student::with(['school', 'classroom', 'guardian']);

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('nis', 'like', "%{$search}%")
                  ->orWhere('nisn', 'like', "%{$search}%")
                  ->orWhere('rfid_tag', 'like', "%{$search}%");
            });
        }

        if ($request->has('school_id') && $request->school_id != '') {
            $query->where('school_id', $request->school_id);
        }

        $students = $query->latest()->paginate(15);
        $schools = School::all();
        $classrooms = Classroom::all();

        return view('admin.master.students', compact('students', 'schools', 'classrooms'));
    }

    public function storeStudent(Request $request)
    {
        $validated = $request->validate([
            'school_id'    => 'required|exists:schools,id',
            'classroom_id' => 'nullable|exists:classrooms,id',
            'nis'          => 'required|string|unique:students,nis',
            'nisn'         => 'nullable|string',
            'full_name'    => 'required|string',
            'gender'       => 'required',
            'pob'          => 'nullable|string',   // place of birth – matches DB column
            'dob'          => 'nullable|date',     // date of birth  – matches DB column
            'rfid_tag'     => 'nullable|string|unique:students,rfid_tag',
            'status'       => 'nullable|string',
        ]);

        $validated['gender'] = in_array(strtoupper($request->gender), ['L', 'LAKI_LAKI', 'M']) ? 'M' : 'F';
        $validated['status'] = in_array(strtoupper($request->status ?? 'ACTIVE'), ['AKTIF', 'ACTIVE']) ? 'ACTIVE' : ($request->status ?? 'ACTIVE');

        Student::create($validated);

        return redirect()->back()->with('success', 'Data Siswa Baru Berhasil Ditambahkan!');
    }

    /**
     * Export Data Siswa ke CSV/Excel
     */
    public function exportStudents()
    {
        $students = Student::with(['school', 'classroom'])->get();
        $filename = 'export_students_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($students) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'NIS', 'NISN', 'Nama Lengkap', 'Jenis Kelamin', 'Unit Sekolah', 'Kelas', 'RFID Tag', 'Saldo Cashless', 'Status']);

            foreach ($students as $st) {
                fputcsv($file, [
                    $st->id,
                    $st->nis,
                    $st->nisn ?? '-',
                    $st->full_name,
                    $st->gender == 'M' ? 'Laki-Laki' : 'Perempuan',
                    $st->school->name ?? '-',
                    $st->classroom->name ?? '-',
                    $st->rfid_tag ?? '-',
                    $st->savings_balance,
                    $st->status,
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Import Massal Data Siswa dari File CSV
     */
    public function importStudents(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $file = $request->file('csv_file');
        $handle = fopen($file->getPathname(), 'r');
        $header = fgetcsv($handle); // Skip header

        $importedCount = 0;
        $firstSchool = School::first();

        while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
            if (count($data) >= 3) {
                $nis = trim($data[0]);
                $fullName = trim($data[1]);
                $genderInput = strtoupper(trim($data[2] ?? 'L'));
                $gender = in_array($genderInput, ['L', 'LAKI_LAKI', 'M']) ? 'M' : 'F';

                Student::firstOrCreate(
                    ['nis' => $nis],
                    [
                        'school_id' => $firstSchool->id ?? 1,
                        'full_name' => $fullName,
                        'gender' => $gender,
                        'rfid_tag' => 'RFID-IMP-' . rand(10000, 99999),
                        'status' => 'ACTIVE',
                        'savings_balance' => 50000,
                    ]
                );
                $importedCount++;
            }
        }
        fclose($handle);

        return redirect()->back()->with('success', "✓ Berhasil mengimpor {$importedCount} data siswa baru secara massal!");
    }

    /**
     * Export Data Guru/Pendidik ke CSV
     */
    public function exportTeachers()
    {
        $teachers = Employee::where('role_type', 'TEACHER')->get();
        $filename = 'export_teachers_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($teachers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'NIP', 'Nama Guru', 'Gelar Depan', 'Gelar Belakang', 'Role', 'Status Kepegawaian', 'Unit Sekolah', 'No. HP', 'Email']);

            foreach ($teachers as $t) {
                fputcsv($file, [
                    $t->id,
                    $t->nip ?? '-',
                    $t->full_name,
                    $t->title_prefix ?? '-',
                    $t->title_suffix ?? '-',
                    $t->role_type ?? 'TEACHER',
                    $t->employment_status ?? 'PERMANENT',
                    $t->school->name ?? '-',
                    $t->phone ?? '-',
                    $t->email ?? '-',
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Kelola Data Guru & Staf Pengajar
     */
    public function teachers()
    {
        $teachers = Employee::where('role_type', 'TEACHER')->with('school')->latest()->paginate(15);
        $schools  = School::all();

        return view('admin.master.teachers', compact('teachers', 'schools'));
    }

    public function storeTeacher(Request $request)
    {
        $validated = $request->validate([
            'school_id'        => 'required|exists:schools,id',
            'nip'              => 'nullable|string|unique:employees,nip',
            'full_name'        => 'required|string',
            'title_prefix'     => 'nullable|string',   // e.g. 'Ustdz.' – matches DB column
            'title_suffix'     => 'nullable|string',   // e.g. 'S.Pd.' – matches DB column
            'gender'           => 'nullable|in:M,F',
            'role_type'        => 'required|in:TEACHER,STAFF,HEADMASTER,COUNSELOR,TREASURER',
            'employment_status'=> 'nullable|in:PERMANENT,CONTRACT,HONORARY',
            'phone'            => 'nullable|string',
            'email'            => 'nullable|email',
        ]);

        // Default values
        $validated['role_type']         = $validated['role_type'] ?? 'TEACHER';
        $validated['employment_status'] = $validated['employment_status'] ?? 'PERMANENT';

        Employee::create($validated);

        return redirect()->back()->with('success', 'Data Guru/Pendidik Berhasil Ditambahkan!');
    }

    /**
     * Kelola Karyawan Non-Guru (TU, CS, Security)
     */
    public function employees()
    {
        $employees = Employee::whereIn('role_type', ['STAFF'])->with('school')->latest()->paginate(15);
        // Also fetch non-TEACHER staff: STAFF, COUNSELOR, TREASURER, etc.
        if ($employees->isEmpty()) {
            $employees = Employee::where('role_type', '!=', 'TEACHER')->with('school')->latest()->paginate(15);
        }
        $schools = School::all();

        return view('admin.master.employees', compact('employees', 'schools'));
    }

    /**
     * Kelola Referensi Mata Pelajaran & Ruangan
     */
    public function references()
    {
        $subjects = Subject::with('school')->get();
        $rooms = Room::with('school')->get();
        $schools = School::all();

        return view('admin.master.references', compact('subjects', 'rooms', 'schools'));
    }

    public function storeSubject(Request $request)
    {
        $validated = $request->validate([
            'school_id' => 'required|exists:schools,id',
            'code' => 'required|string',
            'name' => 'required|string',
            'group' => 'required|string',
        ]);

        Subject::create($validated);

        return redirect()->back()->with('success', 'Mata Pelajaran Berhasil Ditambahkan!');
    }

    public function storeRoom(Request $request)
    {
        $validated = $request->validate([
            'school_id' => 'required|exists:schools,id',
            'code' => 'required|string',
            'name' => 'required|string',
            'building' => 'nullable|string',
            'capacity' => 'required|integer',
        ]);

        Room::create($validated);

        return redirect()->back()->with('success', 'Ruangan Berhasil Ditambahkan!');
    }
}
