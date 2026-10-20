@extends('layouts.app')
@section('title', 'Data Gardu')

@section('content')
    <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Data Gardu</h1>
            <p class="text-sm text-slate-500">Kelola data identitas gardu.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('gardu.export', request()->query()) }}" class="btn btn-success">
                <x-icon name="download" class="h-4 w-4" /> Download Excel
            </a>
            <a href="{{ route('gardu.create') }}" class="btn btn-primary"><x-icon name="plus" class="h-4 w-4" /> Tambah Gardu</a>
        </div>
    </div>

    <div class="card">
       <form method="GET" class="space-y-3 p-4">
            <div class="grid gap-3 sm:grid-cols-3">
                <x-cascade-select :filter="true" :submit="true" :with-gardu="true"
                                :ruas-id="$ruasId" :gerbang-id="$gerbangId" :gardu-id="$garduId" />
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-4">
                    <label class="flex items-center gap-2 text-sm text-slate-600">
                        Show
                        <select name="per_page" data-autosubmit class="input w-auto py-1.5">
                            @foreach ([10, 25, 50, 100] as $n)
                                <option value="{{ $n }}" @selected($perPage === $n)>{{ $n }}</option>
                            @endforeach
                        </select>
                        entries
                    </label>
                    @if ($ruasId || $gerbangId || $garduId)
                        <a href="{{ route('gardu.index') }}" class="text-xs text-blue-600 hover:underline">Reset filter</a>
                    @endif
                </div>
                <div class="relative w-full sm:w-64">
                    <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                    <input type="search" name="q" value="{{ $q }}" placeholder="Cari gardu..." class="input pl-9">
                </div>
            </div>
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
                        <th class="th w-36 text-center">Aksi</th>
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
                            <td class="td">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('gardu.show', $g) }}" class="btn-icon bg-slate-500 hover:bg-slate-600" title="Detail"><x-icon name="eye" class="h-4 w-4" /></a>
                                    <a href="{{ route('gardu.edit', $g) }}" class="btn-icon bg-blue-600 hover:bg-blue-700" title="Edit"><x-icon name="edit" class="h-4 w-4" /></a>
                                    <form method="POST" action="{{ route('gardu.destroy', $g) }}" data-confirm="Hapus gardu {{ $g->nomor_gardu }} - {{ $g->gerbang->nama_gerbang }}?">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-icon bg-red-500 hover:bg-red-600" title="Hapus"><x-icon name="trash" class="h-4 w-4" /></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="td py-10 text-center text-slate-400">Data gardu tidak ditemukan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 p-4 text-sm text-slate-500">
            <span>Showing {{ $gardu->firstItem() ?? 0 }} to {{ $gardu->lastItem() ?? 0 }} of {{ $gardu->total() }} entries</span>
            {{ $gardu->links('pagination.intracs') }}
        </div>
    </div>
@endsection