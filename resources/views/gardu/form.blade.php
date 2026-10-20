@extends('layouts.app')
@section('title', $gardu->exists ? 'Edit Data Gardu' : 'Tambah Data Gardu')

@php
    $had = count(old()) > 0;
    $num = fn (string $f) => old($f, $perangkat->{$f} ?? 0);
    $err = fn (string $f) => $errors->first($f);
@endphp

@section('content')
    <h1 class="text-2xl font-bold text-slate-900">{{ $gardu->exists ? 'Edit Data Gardu' : 'Tambah Data Gardu' }}</h1>
    <p class="mb-4 text-sm text-slate-500">Isi informasi identitas gardu.</p>

    <form method="POST" action="{{ $gardu->exists ? route('gardu.update', $gardu) : route('gardu.store') }}" class="card space-y-6 p-6">
        @csrf
        @if ($gardu->exists) @method('PUT') @endif

        <div class="grid gap-4 md:grid-cols-2">
            <x-cascade-select :required="true" :pick-gardu="true"
                  :ruas-id="old('ruas_id', $gardu->gerbang?->ruas_id)"
                  :gerbang-id="old('gerbang_id', $gardu->gerbang_id)"
                  :current-id="$gardu->id"
                  :nomor="old('nomor_gardu', $gardu->nomor_gardu)" />

            <div>
                <label for="tipe_gardu_id" class="label">Tipe Gardu</label>
                <select id="tipe_gardu_id" name="tipe_gardu_id" class="input">
                    <option value="">Pilih Tipe Gardu</option>
                    @foreach ($tipeList as $t)
                        <option value="{{ $t->id }}" @selected(old('tipe_gardu_id', $gardu->tipe_gardu_id) == $t->id)>{{ $t->nama_tipe }}</option>
                    @endforeach
                </select>
                @if ($err('tipe_gardu_id')) <p class="mt-1 text-xs text-red-600">{{ $err('tipe_gardu_id') }}</p> @endif
            </div>

            <div>
                <label for="status" class="label">Status <span class="text-red-500">*</span></label>
                <select id="status" name="status" required class="input">
                    @foreach (\App\Models\Gardu::STATUS as $s)
                        <option value="{{ $s }}" @selected(old('status', $gardu->status ?: 'Aktif') === $s)>{{ $s }}</option>
                    @endforeach
                </select>
                @if ($err('status')) <p class="mt-1 text-xs text-red-600">{{ $err('status') }}</p> @endif
            </div>

            <div>
                <label for="arah" class="label">Arah</label>
                <select id="arah" name="arah" class="input">
                    <option value="">-</option>
                    @foreach (\App\Models\Gardu::ARAH as $a)
                        <option value="{{ $a }}" @selected(old('arah', $gardu->arah) === $a)>{{ $a }}</option>
                    @endforeach
                </select>
                @if ($err('arah')) <p class="mt-1 text-xs text-red-600">{{ $err('arah') }}</p> @endif
            </div>

            <div>
                <label for="catatan" class="label">Catatan</label>
                <input id="catatan" name="catatan" type="text" value="{{ old('catatan', $gardu->catatan) }}" class="input">
                @if ($err('catatan')) <p class="mt-1 text-xs text-red-600">{{ $err('catatan') }}</p> @endif
            </div>
        </div>
        @include('partials.cascade-data')
        <div>
            <h2 class="mb-3 text-sm font-semibold text-slate-800">Perangkat dan Sistem</h2>

            @php
                $groups = [
                    ['Jumlah Reader', ['reader_atas' => 'Atas', 'reader_bawah' => 'Bawah']],
                    ['CCTV', ['cctv_lajur' => 'Lajur', 'cctv_gandar' => 'Gandar']],
                ];

                $lprFields = [
                    'lpr_custom' => 'Custom',
                    'pyramid_atas' => 'Pyramid Atas',
                    'pyramid_bawah' => 'Pyramid Bawah',
                    'hs_atas' => 'HS Atas',
                    'hs_bawah' => 'HS Bawah',
                ];
            @endphp

            <div class="grid grid-flow-dense gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($groups as [$title, $fields])
                    <fieldset class="rounded-lg border border-slate-200 p-3">
                        <legend class="px-1 text-xs font-semibold text-slate-700">{{ $title }}</legend>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach ($fields as $name => $label)
                                <div>
                                    <label for="{{ $name }}" class="label">{{ $label }}</label>
                                    <input id="{{ $name }}" name="{{ $name }}" type="number" min="0" max="99" value="{{ $num($name) }}" class="input">
                                    @if ($err($name)) <p class="mt-1 text-xs text-red-600">{{ $err($name) }}</p> @endif
                                </div>
                            @endforeach
                        </div>
                    </fieldset>
                @endforeach

                <fieldset class="rounded-lg border border-slate-200 p-3">
                    <legend class="px-1 text-xs font-semibold text-slate-700">ALB</legend>
                    <div class="space-y-2">
                        @foreach (\App\Models\PerangkatGardu::ALB_FIELDS as $name => $label)
                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" name="{{ $name }}" value="1" class="rounded border-slate-300 text-blue-600"
                                    @checked($had ? (bool) old($name) : (bool) ($perangkat->{$name} ?? false))>
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <fieldset class="rounded-lg border border-slate-200 p-3 sm:col-span-2">
                    <legend class="px-1 text-xs font-semibold text-slate-700">LPR</legend>
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-5">
                        @foreach ($lprFields as $name => $label)
                            <div>
                                <label for="{{ $name }}" class="label">{{ $label }}</label>
                                <input id="{{ $name }}" name="{{ $name }}" type="number" min="0" max="99" value="{{ $num($name) }}" class="input">
                                @if ($err($name)) <p class="mt-1 text-xs text-red-600">{{ $err($name) }}</p> @endif
                            </div>
                        @endforeach
                    </div>
                </fieldset>

                <fieldset class="rounded-lg border border-slate-200 p-3">
                    <legend class="px-1 text-xs font-semibold text-slate-700">Printer CSS</legend>
                    <input id="printer_css" name="printer_css" type="text" value="{{ old('printer_css', $perangkat->printer_css) }}" placeholder="Contoh: HP LaserJet P1101" class="input">
                    @if ($err('printer_css')) <p class="mt-1 text-xs text-red-600">{{ $err('printer_css') }}</p> @endif
                </fieldset>
            </div>
        </div>

        <div class="flex items-center justify-between border-t border-slate-100 pt-4">
            <a href="{{ $gardu->exists ? route('gardu.show', $gardu) : route('gardu.index') }}" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
    </form>
@endsection