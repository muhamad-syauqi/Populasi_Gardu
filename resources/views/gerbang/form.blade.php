@extends('layouts.app')
@section('title', $gerbang->exists ? 'Edit Gerbang' : 'Tambah Gerbang')

@section('content')
    <h1 class="text-2xl font-bold text-slate-900">{{ $gerbang->exists ? 'Edit Gerbang' : 'Tambah Gerbang' }}</h1>
    <p class="mb-4 text-sm text-slate-500">Isi informasi gerbang tol.</p>

    <form method="POST" action="{{ $gerbang->exists ? route('gerbang.update', $gerbang) : route('gerbang.store') }}" class="card max-w-2xl space-y-4 p-6">
        @csrf
        @if ($gerbang->exists) @method('PUT') @endif

        <div>
            <label for="ruas_id" class="label">Ruas Tol <span class="text-red-500">*</span></label>
            <select id="ruas_id" name="ruas_id" required class="input">
                <option value="">Pilih Ruas Tol</option>
                @foreach ($ruas as $r)
                    <option value="{{ $r->id }}" @selected(old('ruas_id', $gerbang->ruas_id) == $r->id)>{{ $r->nama_ruas }}</option>
                @endforeach
            </select>
            @error('ruas_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="nama_gerbang" class="label">Nama Gerbang <span class="text-red-500">*</span></label>
            <input id="nama_gerbang" name="nama_gerbang" type="text" required value="{{ old('nama_gerbang', $gerbang->nama_gerbang) }}" placeholder="Contoh: Pasteur" class="input">
            @error('nama_gerbang') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="lokasi" class="label">Lokasi</label>
            <input id="lokasi" name="lokasi" type="text" value="{{ old('lokasi', $gerbang->lokasi) }}" placeholder="Contoh: Bandung" class="input">
            @error('lokasi') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center justify-between border-t border-slate-100 pt-4">
            <a href="{{ route('gerbang.index') }}" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
    </form>
@endsection