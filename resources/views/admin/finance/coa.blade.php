@extends('admin.layout')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
        <div>
            <span class="px-3 py-1 rounded-full bg-emerald-100 text-emerald-800 font-extrabold text-[10px] uppercase">Modul 4: Sub-Modul 4.2</span>
            <h1 class="text-2xl font-black text-slate-900 mt-1">Chart of Accounts (COA) & Jurnal Akuntansi</h1>
            <p class="text-xs text-slate-500 font-medium">Bagan akun standar akuntansi sekolah, kas/bank, pendapatan SPP, & pencatatan jurnal otomatis.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- Table COA -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
            <h3 class="font-black text-base text-slate-900">📊 Chart of Accounts (COA) Sekolah</h3>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold uppercase">
                        <tr>
                            <th class="p-3">Kode COA</th>
                            <th class="p-3">Nama Akun</th>
                            <th class="p-3">Tipe</th>
                            <th class="p-3">Saldo Akun</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium text-slate-800">
                        @foreach($coas as $c)
                        <tr class="hover:bg-slate-50">
                            <td class="p-3 font-mono font-black text-emerald-700">{{ $c->code }}</td>
                            <td class="p-3 font-bold text-slate-900">{{ $c->name }}</td>
                            <td class="p-3">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700">
                                    {{ $c->type }}
                                </span>
                            </td>
                            <td class="p-3 font-black text-slate-900">Rp {{ number_format($c->current_balance, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Form Tambah COA -->
            <form action="{{ route('admin.finance.coa.store') }}" method="POST" class="pt-4 border-t border-slate-100 space-y-3 text-xs font-bold">
                @csrf
                <span class="block text-slate-800 font-extrabold">➕ Tambah Akun COA Baru</span>
                <input type="hidden" name="school_id" value="{{ $schools->first()->id ?? 1 }}">

                <div class="grid grid-cols-2 gap-2">
                    <input type="text" name="code" required placeholder="Kode (102)" class="px-3 py-2 rounded-xl border border-slate-300">
                    <input type="text" name="name" required placeholder="Nama Akun (Bank Syariah)" class="px-3 py-2 rounded-xl border border-slate-300">
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <select name="type" required class="px-3 py-2 rounded-xl border border-slate-300">
                        <option value="ASSET">ASSET (Aset/Kas)</option>
                        <option value="LIABILITY">LIABILITY (Kewajiban)</option>
                        <option value="EQUITY">EQUITY (Modal)</option>
                        <option value="REVENUE">REVENUE (Pendapatan)</option>
                        <option value="EXPENSE">EXPENSE (Beban Operasional)</option>
                    </select>
                    <input type="number" name="current_balance" value="0" required placeholder="Saldo Awal" class="px-3 py-2 rounded-xl border border-slate-300">
                </div>

                <button type="submit" class="w-full py-2.5 rounded-xl bg-emerald-600 text-white font-extrabold">
                    Simpan Akun COA ➔
                </button>
            </form>
        </div>

        <!-- Table Jurnal Otomatis -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
            <h3 class="font-black text-base text-slate-900">📖 Jurnal Umum Akuntansi (Auto-Post)</h3>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold uppercase">
                        <tr>
                            <th class="p-3">Ref No / Tgl</th>
                            <th class="p-3">Akun COA</th>
                            <th class="p-3">Debit (Rp)</th>
                            <th class="p-3">Kredit (Rp)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium text-slate-800">
                        @foreach($journals as $j)
                        <tr class="hover:bg-slate-50">
                            <td class="p-3">
                                <span class="font-mono font-bold text-slate-900 block">{{ $j->reference_number }}</span>
                                <span class="text-[10px] text-slate-400">{{ $j->date }}</span>
                            </td>
                            <td class="p-3 font-bold text-slate-900">
                                {{ $j->coa->name ?? '-' }}
                                <span class="text-[10px] text-slate-400 block font-normal">{{ $j->description }}</span>
                            </td>
                            <td class="p-3 font-bold text-emerald-700">{{ number_format($j->debit, 0, ',', '.') }}</td>
                            <td class="p-3 font-bold text-rose-700">{{ number_format($j->credit, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
@endsection
