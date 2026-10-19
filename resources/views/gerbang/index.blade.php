@extends('layouts.app')
@section('title', 'Data Gerbang')

@section('content')
    <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Data Gerbang</h1>
            <p class="text-sm text-slate-500">Kelola data gerbang tol.</p>
        </div>
        <a href="{{ route('gerbang.create') }}" class="btn btn-primary"><x-icon name="plus" class="h-4 w-4" /> Tambah Gerbang</a>
    </div>

    <div class="card">
        <form method="GET" class="flex flex-wrap items-center justify-between gap-3 p-4">
            <label class="flex items-center gap-2 text-sm text-slate-600">
                Show
                <select name="per_page" data-autosubmit class="input w-auto py-1.5">
                    @foreach ([10, 25, 50, 100] as $n)
                        <option value="{{ $n }}" @selected($perPage === $n)>{{ $n }}</option>
                    @endforeach
                </select>
                entries
            </label>
            <div class="relative w-full sm:w-64">
                <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                <input type="search" name="q" value="{{ $q }}" placeholder="Cari gerbang..." class="input pl-9">
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr>
                        <th class="th w-14">No</th>
                        <th class="th">Nama Gerbang</th>
                        <th class="th">Ruas Tol</th>
                        <th class="th">Lokasi</th>
                        <th class="th text-center">Jumlah Gardu</th>
                        <th class="th w-28 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($gerbang as $g)
                        <tr class="hover:bg-slate-50">
                            <td class="td">{{ $gerbang->firstItem() + $loop->index }}</td>
                            <td class="td font-medium text-slate-900">{{ $g->nama_gerbang }}</td>
                            <td class="td">{{ $g->ruas->nama_ruas ?? '-' }}</td>
                            <td class="td">{{ $g->lokasi ?: '-' }}</td>
                            <td class="td text-center">{{ $g->gardu_count }}</td>
                            <td class="td">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('gerbang.edit', $g) }}" class="btn-icon bg-blue-600 hover:bg-blue-700" title="Edit"><x-icon name="edit" class="h-4 w-4" /></a>
                                    <form method="POST" action="{{ route('gerbang.destroy', $g) }}" data-confirm="Hapus gerbang {{ $g->nama_gerbang }}?">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-icon bg-red-500 hover:bg-red-600" title="Hapus"><x-icon name="trash" class="h-4 w-4" /></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="td py-10 text-center text-slate-400">Data gerbang tidak ditemukan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 p-4 text-sm text-slate-500">
            <span>Showing {{ $gerbang->firstItem() ?? 0 }} to {{ $gerbang->lastItem() ?? 0 }} of {{ $gerbang->total() }} entries</span>
            {{ $gerbang->links('pagination.intracs') }}
        </div>
    </div>
@endsection