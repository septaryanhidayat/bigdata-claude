@extends('admin.layout')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
        <div>
            <span class="px-3 py-1 rounded-full bg-emerald-100 text-emerald-800 font-extrabold text-[10px] uppercase">Modul 1: Sub-Modul 6</span>
            <h1 class="text-2xl font-black text-slate-900 mt-1">Data Siswa & Wali Murid</h1>
            <p class="text-xs text-slate-500 font-medium">CRUD biodata siswa, tag kartu RFID gate, riwayat rombel, status aktif/lulus/mutasi, serta import/export.</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('admin.master.students.export') }}" class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 font-extrabold text-xs border border-slate-300 transition-colors inline-flex items-center gap-1">
                📥 Export CSV Siswa
            </a>
            <button onclick="document.getElementById('importModal').classList.remove('hidden')" class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs transition-colors shadow">
                📤 Import Data CSV
            </button>
        </div>
    </div>

    <!-- Modal Import CSV -->
    <div id="importModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center hidden p-4">
        <div class="bg-white rounded-3xl p-6 max-w-md w-full shadow-2xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-base font-black text-slate-900">Import Massal Data Siswa (CSV)</h3>
                <button onclick="document.getElementById('importModal').classList.add('hidden')" class="text-slate-400 font-bold">✕</button>
            </div>
            <form action="{{ route('admin.master.students.import') }}" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs font-bold">
                @csrf
                <div>
                    <label class="block text-slate-700 mb-1">Pilih File CSV Data Siswa:</label>
                    <input type="file" name="csv_file" accept=".csv,.txt" required class="w-full p-3 rounded-2xl bg-slate-50 border border-slate-200">
                    <p class="text-[10px] text-slate-400 mt-1 font-normal">Format CSV: NIS, Nama_Lengkap, Jenis_Kelamin (L/P)</p>
                </div>
                <div class="flex items-center justify-end gap-2 border-t border-slate-100 pt-3">
                    <button type="button" onclick="document.getElementById('importModal').classList.add('hidden')" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-600">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 text-white font-black">Unggah & Import</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
        <form action="{{ route('admin.master.students') }}" method="GET" class="flex flex-col sm:flex-row items-center gap-3">
            <div class="flex-1 w-full">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari NIS, NISN, Nama Siswa, atau Kode RFID Tag..." class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs font-bold">
            </div>

            <div class="w-full sm:w-48">
                <select name="school_id" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs font-bold">
                    <option value="">Semua Unit Sekolah</option>
                    @foreach($schools as $sc)
                        <option value="{{ $sc->id }}" {{ request('school_id') == $sc->id ? 'selected' : '' }}>{{ $sc->name }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="w-full sm:w-auto px-6 py-2.5 rounded-xl bg-slate-900 text-white font-extrabold text-xs">
                🔍 Filter Data
            </button>
        </form>
    </div>

    <!-- Student Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-700 font-bold uppercase tracking-wider">
                    <tr>
                        <th class="py-3.5 px-4">NIS / RFID Tag</th>
                        <th class="py-3.5 px-4">Nama Lengkap Siswa</th>
                        <th class="py-3.5 px-4">Unit & Rombel</th>
                        <th class="py-3.5 px-4">Orang Tua / Wali</th>
                        <th class="py-3.5 px-4">Limit Kantin</th>
                        <th class="py-3.5 px-4">Saldo Tabungan</th>
                        <th class="py-3.5 px-4 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @foreach($students as $st)
                    <tr class="hover:bg-slate-50">
                        <td class="py-3.5 px-4">
                            <span class="font-extrabold text-slate-900 block">{{ $st->nis }}</span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-slate-100 text-slate-600 border">🪪 {{ $st->rfid_tag ?? 'Belum Di-Tap' }}</span>
                        </td>
                        <td class="py-3.5 px-4">
                            <h4 class="font-bold text-slate-900">{{ $st->full_name }}</h4>
                            <span class="text-[10px] text-slate-400">NISN: {{ $st->nisn ?? '-' }} &bull; {{ $st->gender == 'M' ? 'Laki-Laki' : 'Perempuan' }}</span>
                        </td>
                        <td class="py-3.5 px-4">
                            <span class="font-bold text-emerald-700 block">{{ $st->school->code ?? '-' }}</span>
                            <span class="text-[11px] text-slate-500">{{ $st->classroom->name ?? 'Belum ada rombel' }}</span>
                        </td>
                        <td class="py-3.5 px-4">
                            <span class="font-bold text-slate-800 block">{{ $st->guardian->full_name ?? '-' }}</span>
                            <span class="text-[10px] text-slate-400">HP: {{ $st->guardian->phone ?? '-' }}</span>
                        </td>
                        <td class="py-3.5 px-4 font-bold text-slate-700">
                            Rp {{ number_format($st->canteen_daily_limit, 0, ',', '.') }}/hari
                        </td>
                        <td class="py-3.5 px-4 font-extrabold text-emerald-600">
                            Rp {{ number_format($st->savings_balance, 0, ',', '.') }}
                        </td>
                        <td class="py-3.5 px-4 text-center">
                            @if(in_array($st->status, ['AKTIF', 'ACTIVE']))
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">AKTIF</span>
                            @elseif(in_array($st->status, ['LULUS', 'GRADUATED']))
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800">LULUS</span>
                            @else
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800">{{ $st->status }}</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-100">
            {{ $students->links() }}
        </div>
    </div>

    <!-- Form Tambah Siswa Baru -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
        <h3 class="font-black text-base text-slate-900">➕ Input Data Siswa Baru</h3>

        <form action="{{ route('admin.master.students.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs font-bold">
            @csrf
            <div>
                <label class="block text-slate-700 mb-1">Unit Sekolah</label>
                <select name="school_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300">
                    @foreach($schools as $sc)
                        <option value="{{ $sc->id }}">{{ $sc->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-slate-700 mb-1">Rombel / Kelas</label>
                <select name="classroom_id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300">
                    <option value="">-- Tanpa Kelas --</option>
                    @foreach($classrooms as $cls)
                        <option value="{{ $cls->id }}">{{ $cls->name }} ({{ $cls->school->code ?? '' }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-slate-700 mb-1">Status Keaktifan</label>
                <select name="status" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300">
                    <option value="AKTIF">AKTIF</option>
                    <option value="LULUS">LULUS</option>
                    <option value="KELUAR">KELUAR</option>
                    <option value="MUTASI">MUTASI</option>
                </select>
            </div>

            <div>
                <label class="block text-slate-700 mb-1">NIS (Nomor Induk Siswa)</label>
                <input type="text" name="nis" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300" placeholder="20267002">
            </div>

            <div>
                <label class="block text-slate-700 mb-1">NISN Nasional</label>
                <input type="text" name="nisn" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300" placeholder="0087654321">
            </div>

            <div>
                <label class="block text-slate-700 mb-1">Kode Kartu RFID Gate Scanner</label>
                <input type="text" name="rfid_tag" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300" placeholder="RFID-STU-7002">
            </div>

            <div class="md:col-span-2">
                <label class="block text-slate-700 mb-1">Nama Lengkap Siswa</label>
                <input type="text" name="full_name" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300" placeholder="Aisyah Nur Syafiqah">
            </div>

            <div>
                <label class="block text-slate-700 mb-1">Jenis Kelamin</label>
                <select name="gender" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300">
                    <option value="M">Laki-Laki (Ikhwan)</option>
                    <option value="F">Perempuan (Akhwat)</option>
                </select>
            </div>

            <div>
                <label class="block text-slate-700 mb-1">Tempat Lahir</label>
                <input type="text" name="pob" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300" placeholder="Jakarta">
            </div>

            <div>
                <label class="block text-slate-700 mb-1">Tanggal Lahir</label>
                <input type="date" name="dob" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300">
            </div>

            <div class="md:col-span-3 pt-2">
                <button type="submit" class="px-6 py-3 rounded-xl bg-emerald-600 text-white font-extrabold hover:bg-emerald-700 transition-colors shadow">
                    Simpan Data Siswa Baru ➔
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
