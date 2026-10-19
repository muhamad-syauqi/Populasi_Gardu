@extends('layouts.app')
@section('title', 'Laporan Identitas Gardu')

@section('content')
    <h1 class="text-2xl font-bold text-slate-900">Laporan Identitas Gardu</h1>
    <p class="mb-4 text-sm text-slate-500">Lihat dan unduh data identitas gardu dalam format Excel.</p>

    <div class="card">
        <form method="GET" action="{{ route('laporan.index') }}" class="grid gap-3 border-b border-slate-100 p-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-cascade-select :filter="true" :with-gardu="true"
                            :ruas-id="$filters['ruas_id'] ?? null"
                            :gerbang-id="$filters['gerbang_id'] ?? null"
                            :gardu-id="$filters['gardu_id'] ?? null" />

            <div>
                <label class="label">Tipe Gardu</label>
                <select name="tipe_gardu_id" class="input">
                    <option value="">Semua Tipe</option>
                    @foreach ($tipeList as $t)
                        <option value="{{ $t->id }}" @selected(($filters['tipe_gardu_id'] ?? '') == $t->id)>{{ $t->nama_tipe }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Status</label>
                <select name="status" class="input">
                    <option value="">Semua Status</option>
                    @foreach (\App\Models\Gardu::STATUS as $s)
                        <option value="{{ $s }}" @selected(($filters['status'] ?? '') === $s)>{{ $s }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Tanggal Input (dari)</label>
                <input type="date" name="dari" value="{{ $filters['dari'] ?? '' }}" class="input">
            </div>
            <div>
                <label class="label">Tanggal Input (sampai)</label>
                <input type="date" name="sampai" value="{{ $filters['sampai'] ?? '' }}" class="input">
            </div>
            <div class="flex items-end">
                <button type="submit" class="btn btn-primary w-full">Tampilkan</button>
            </div>
            @error('sampai') <p class="text-xs text-red-600 sm:col-span-2 lg:col-span-4">{{ $message }}</p> @enderror
        </form>
        @include('partials.cascade-data')

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr>
                        <th class="th w-14">No</th>
                        <th class="th">Gerbang</th>
                        <th class="th">No Gardu</th>
                        <th class="th">Tipe Gardu</th>
                        <th class="th">Status</th>
                        <th class="th w-24 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($gardu as $g)
                        <tr class="hover:bg-slate-50">
                            <td class="td">{{ $gardu->firstItem() + $loop->index }}</td>
                            <td class="td">{{ $g->gerbang->nama_gerbang }}</td>
                            <td class="td font-medium text-slate-900">{{ $g->nomor_gardu }}</td>
                            <td class="td">{{ $g->tipe->nama_tipe ?? '-' }}</td>
                            <td class="td"><x-status-badge :status="$g->status" /></td>
                            <td class="td text-center">
                                <a href="{{ route('gardu.show', $g) }}" class="btn bg-blue-600 px-3 py-1 text-xs text-white hover:bg-blue-700">Lihat</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="td py-10 text-center text-slate-400">Tidak ada data untuk filter ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 p-4">
            <div class="flex items-center gap-3 text-sm text-slate-500">
                <a href="{{ route('laporan.export', request()->query()) }}" class="btn btn-success"><x-icon name="download" class="h-4 w-4" /> Download Excel</a>
                <span>{{ $gardu->total() }} data</span>
            </div>
            {{ $gardu->links('pagination.intracs') }}
        </div>
    </div>
@endsection