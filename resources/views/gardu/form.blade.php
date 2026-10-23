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
                    ['MR', ['mr_entrance' => 'Entrance', 'mr_exit' => 'Exit']],
                ];

                $cctvGroups = [
                    'Lajur' => ['Vivotek' => 'cctv_vivotek_lajur', 'Milesight' => 'cctv_milesight_lajur'],
                    'Gandar' => ['Vivotek' => 'cctv_vivotek_gandar', 'Milesight' => 'cctv_milesight_gandar'],
                ];

                $lprGroups = [
                    'Custom' => ['lpr_custom_atas', 'lpr_custom_bawah'],
                    'Pyramid' => ['pyramid_atas', 'pyramid_bawah'],
                    'HS' => ['hs_atas', 'hs_bawah'],
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

                <fieldset class="rounded-lg border border-slate-200 p-3 sm:col-span-2 lg:col-span-3">
                    <legend class="px-1 text-xs font-semibold text-slate-700">CCTV</legend>
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach ($cctvGroups as $jenis => $merekList)
                            <div class="rounded-lg border border-slate-200 p-3">
                                <div class="mb-2 text-xs font-semibold text-slate-700">{{ $loop->iteration }}. {{ $jenis }}</div>
                                <div class="grid grid-cols-2 gap-2">
                                    @foreach ($merekList as $merek => $name)
                                        <div>
                                            <label for="{{ $name }}" class="label">{{ $merek }}</label>
                                            <input id="{{ $name }}" name="{{ $name }}" type="number" min="0" max="99" value="{{ $num($name) }}" class="input">
                                            @if ($err($name)) <p class="mt-1 text-xs text-red-600">{{ $err($name) }}</p> @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </fieldset>

                <fieldset class="rounded-lg border border-slate-200 p-3 sm:col-span-2 lg:col-span-3">
                    <legend class="px-1 text-xs font-semibold text-slate-700">LPR</legend>
                    <div class="grid gap-3 sm:grid-cols-3">
                        @foreach ($lprGroups as $jenis => [$atas, $bawah])
                            <div class="rounded-lg border border-slate-200 p-3">
                                <div class="mb-2 text-xs font-semibold text-slate-700">{{ $loop->iteration }}. {{ $jenis }}</div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label for="{{ $atas }}" class="label">Atas</label>
                                        <input id="{{ $atas }}" name="{{ $atas }}" type="number" min="0" max="99" value="{{ $num($atas) }}" class="input">
                                        @if ($err($atas)) <p class="mt-1 text-xs text-red-600">{{ $err($atas) }}</p> @endif
                                    </div>
                                    <div>
                                        <label for="{{ $bawah }}" class="label">Bawah</label>
                                        <input id="{{ $bawah }}" name="{{ $bawah }}" type="number" min="0" max="99" value="{{ $num($bawah) }}" class="input">
                                        @if ($err($bawah)) <p class="mt-1 text-xs text-red-600">{{ $err($bawah) }}</p> @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </fieldset>

                <fieldset class="rounded-lg border border-slate-200 p-3">
                    <legend class="px-1 text-xs font-semibold text-slate-700">Printer CS (Pultol)</legend>
                    <select id="printer_css" name="printer_css" class="input">
                        <option value="">Pilih printer</option>
                        @foreach (\App\Models\PerangkatGardu::PRINTER_OPTIONS as $printer)
                            <option value="{{ $printer }}" @selected(old('printer_css', $perangkat->printer_css) === $printer)>{{ $printer }}</option>
                        @endforeach
                    </select>
                    @if ($err('printer_css')) <p class="mt-1 text-xs text-red-600">{{ $err('printer_css') }}</p> @endif
                </fieldset>

                <fieldset class="rounded-lg border border-slate-200 p-3">
                    <legend class="px-1 text-xs font-semibold text-slate-700">Printer CSS</legend>
                    <select id="printer_css_2" name="printer_css_2" class="input">
                        <option value="">Pilih printer</option>
                        @foreach (\App\Models\PerangkatGardu::PRINTER_CSS_OPTIONS as $printer)
                            <option value="{{ $printer }}" @selected(old('printer_css_2', $perangkat->printer_css_2) === $printer)>{{ $printer }}</option>
                        @endforeach
                    </select>
                    @if ($err('printer_css_2')) <p class="mt-1 text-xs text-red-600">{{ $err('printer_css_2') }}</p> @endif
                </fieldset>
            </div>
        </div>

        <div class="flex items-center justify-between border-t border-slate-100 pt-4">
            <a href="{{ $gardu->exists ? route('gardu.show', $gardu) : route('gardu.index') }}" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
    </form>
@endsection