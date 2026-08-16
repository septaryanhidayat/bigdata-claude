@extends('admin.layout')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
        <div>
            <span class="px-3 py-1 rounded-full bg-emerald-100 text-emerald-800 font-extrabold text-[10px] uppercase">Modul 5: Sub-Modul 5.1</span>
            <h1 class="text-2xl font-black text-slate-900 mt-1">Tabungan Siswa & Teller Kasir</h1>
            <p class="text-xs text-slate-500 font-medium">Pengelolaan rekening simpanan siswa, transaksi setor/tarik tunai teller, & saldo akumulasi.</p>
        </div>

        <div class="p-4 rounded-xl bg-slate-900 text-white text-right">
            <span class="text-[10px] text-slate-400 font-bold uppercase block">Total Saldo Tabungan Siswa</span>
            <span class="text-xl font-black text-amber-300">Rp {{ number_format($totalSavings, 0, ',', '.') }}</span>
        </div>
    </div>

    <!-- Table Mutasi Tabungan -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-black text-base text-slate-900">Riwayat Mutasi Tabungan Siswa</h3>
            <span class="text-xs text-slate-400 font-bold">Total Transaksi: {{ $transactions->total() }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-700 font-bold uppercase">
                    <tr>
                        <th class="p-4">Tanggal / Waktu</th>
                        <th class="p-4">Nama Siswa</th>
                        <th class="p-4">Jenis Transaksi</th>
                        <th class="p-4">Nominal Transaksi</th>
                        <th class="p-4">Saldo Akhir</th>
                        <th class="p-4">Catatan Teller</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-slate-800">
                    @foreach($transactions as $tx)
                    <tr class="hover:bg-slate-50">
                        <td class="p-4 font-mono text-slate-500">{{ $tx->created_at->format('Y-m-d H:i') }}</td>
                        <td class="p-4">
                            <span class="font-black text-slate-900 block">{{ $tx->student->full_name ?? '-' }}</span>
                            <span class="text-[10px] text-slate-400">NIS: {{ $tx->student->nis ?? '-' }} • {{ $tx->student->classroom->name ?? '-' }}</span>
                        </td>
                        <td class="p-4">
                            @if($tx->type == 'DEPOSIT')
                                <span class="px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800 font-black text-[10px]">🟢 SETOR TUNAI</span>
                            @elseif($tx->type == 'TRANSFER_SPP')
                                <span class="px-2.5 py-1 rounded-full bg-blue-100 text-blue-800 font-black text-[10px]">🟦 TRANSFER SPP</span>
                            @else
                                <span class="px-2.5 py-1 rounded-full bg-rose-100 text-rose-800 font-black text-[10px]">🔴 TARIK TUNAI</span>
                            @endif
                        </td>
                        <td class="p-4 font-black text-slate-900">Rp {{ number_format($tx->amount, 0, ',', '.') }}</td>
                        <td class="p-4 font-extrabold text-emerald-700">Rp {{ number_format($tx->balance_after, 0, ',', '.') }}</td>
                        <td class="p-4 text-slate-500">{{ $tx->description }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-100">
            {{ $transactions->links() }}
        </div>
    </div>

    <!-- Form Transaksi Teller Tabungan -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
        <h3 class="font-black text-base text-slate-900">➕ Form Transaksi Teller Tabungan (Setor / Tarik)</h3>

        <form action="{{ route('admin.savings.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs font-bold">
            @csrf
            <div>
                <label class="block text-slate-700 mb-1">Pilih Siswa</label>
                <select name="student_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300">
                    @foreach($students as $st)
                        <option value="{{ $st->id }}">{{ $st->full_name }} (Saldo: Rp {{ number_format($st->savings_balance, 0, ',', '.') }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-slate-700 mb-1">Jenis Transaksi</label>
                <select name="type" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300">
                    <option value="DEPOSIT">🟢 SETOR TUNAI</option>
                    <option value="WITHDRAWAL">🔴 TARIK TUNAI</option>
                </select>
            </div>

            <div>
                <label class="block text-slate-700 mb-1">Nominal Transaksi (Rp)</label>
                <input type="number" name="amount" value="50000" min="1000" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300">
            </div>

            <div class="md:col-span-3">
                <label class="block text-slate-700 mb-1">Catatan Transaksi Teller</label>
                <input type="text" name="description" placeholder="Setoran tabungan mingguan" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300">
            </div>

            <div class="md:col-span-3 pt-2">
                <button type="submit" class="px-6 py-3 rounded-xl bg-emerald-600 text-white font-extrabold hover:bg-emerald-700 transition-colors shadow">
                    Proses Transaksi Teller ➔
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
