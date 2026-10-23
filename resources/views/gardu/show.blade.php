@extends('layouts.app')
@section('title', 'Detail Gardu')

@php
    $p = $gardu->perangkat;
    $n = fn (string $f) => (int) ($p->{$f} ?? 0);

    $cards = [
        ['Reader', [['Atas', $n('reader_atas')], ['Bawah', $n('reader_bawah')]]],
        ['MR', [['Entrance', $n('mr_entrance')], ['Exit', $n('mr_exit')]]],
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

@section('content')
    <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Detail Gardu</h1>
            <nav class="text-sm text-slate-500">
                <a href="{{ route('gardu.index') }}" class="text-blue-600 hover:underline">Data Gardu</a> &rsaquo; Detail
            </nav>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('gardu.edit', $gardu) }}" class="btn btn-primary"><x-icon name="edit" class="h-4 w-4" /> Edit</a>
            <a href="{{ route('gardu.index') }}" class="btn btn-secondary"><x-icon name="arrow-left" class="h-4 w-4" /> Kembali</a>
        </div>
    </div>

    <div class="card p-6">
        <div class="mb-5 flex flex-wrap items-center gap-3">
            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 text-blue-600"><x-icon name="grid" /></span>
            <h2 class="text-lg font-semibold text-slate-900">Gardu {{ $gardu->nomor_gardu }} - {{ $gardu->gerbang->nama_gerbang }}</h2>
            @if ($gardu->tipe)
                <span class="rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-700">{{ $gardu->tipe->nama_tipe }}</span>
            @endif
            <x-status-badge :status="$gardu->status" />
            @if ($gardu->arah)
                <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600">{{ $gardu->arah }}</span>
            @endif
        </div>

        <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['Gerbang', $gardu->gerbang->nama_gerbang, 'building'],
                ['Ruas Tol', $gardu->gerbang->ruas->nama_ruas ?? '-', 'grid'],
                ['Lokasi', $gardu->gerbang->lokasi ?: '-', 'pin'],
                ['Tanggal Input', $gardu->created_at->translatedFormat('d M Y'), 'calendar'],
            ] as [$label, $value, $icon])
                <div class="flex items-center gap-3 rounded-lg border border-slate-200 p-3">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-slate-500"><x-icon :name="$icon" class="h-5 w-5" /></span>
                    <div>
                        <dt class="text-xs text-slate-500">{{ $label }}</dt>
                        <dd class="text-sm font-medium text-slate-900">{{ $value }}</dd>
                    </div>
                </div>
            @endforeach
        </dl>

        @if ($gardu->catatan)
            <p class="mt-4 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-800"><span class="font-medium">Catatan:</span> {{ $gardu->catatan }}</p>
        @endif

        <h3 class="mb-3 mt-6 text-sm font-semibold text-slate-800">Perangkat dan Sistem</h3>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($cards as [$title, $items])
                <div class="rounded-lg border border-slate-200 p-4">
                    <div class="mb-2 text-sm font-semibold text-slate-800">{{ $title }}</div>
                    <div class="flex flex-wrap gap-x-8 gap-y-3">
                        @foreach ($items as [$label, $value])
                            <div>
                                <div class="text-xs text-slate-500">{{ $label }}</div>
                                <div class="text-lg font-semibold text-slate-900">{{ $value }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <div class="rounded-lg border border-slate-200 p-4">
                <div class="mb-2 text-sm font-semibold text-slate-800">ALB</div>
                <ul class="space-y-1 text-sm">
                    @foreach (\App\Models\PerangkatGardu::ALB_FIELDS as $field => $label)
                        <li class="flex items-center justify-between">
                            <span class="text-slate-600">{{ $label }}</span>
                            @if ($p && $p->{$field})
                                <x-icon name="check" class="h-4 w-4 text-emerald-500" />
                            @else
                                <span class="text-slate-300">-</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="rounded-lg border border-slate-200 p-4 sm:col-span-2 lg:col-span-3">
                <div class="mb-3 text-sm font-semibold text-slate-800">CCTV</div>
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($cctvGroups as $jenis => $merekList)
                        <div class="rounded-lg bg-slate-50 p-3">
                            <div class="mb-2 text-xs font-semibold text-slate-700">{{ $loop->iteration }}. {{ $jenis }}</div>
                            <div class="flex gap-8">
                                @foreach ($merekList as $merek => $name)
                                    <div>
                                        <div class="text-xs text-slate-500">{{ $merek }}</div>
                                        <div class="text-lg font-semibold text-slate-900">{{ $n($name) }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-lg border border-slate-200 p-4 sm:col-span-2 lg:col-span-3">
                <div class="mb-3 text-sm font-semibold text-slate-800">LPR</div>
                <div class="grid gap-3 sm:grid-cols-3">
                    @foreach ($lprGroups as $jenis => [$atas, $bawah])
                        <div class="rounded-lg bg-slate-50 p-3">
                            <div class="mb-2 text-xs font-semibold text-slate-700">{{ $loop->iteration }}. {{ $jenis }}</div>
                            <div class="flex gap-8">
                                <div>
                                    <div class="text-xs text-slate-500">Atas</div>
                                    <div class="text-lg font-semibold text-slate-900">{{ $n($atas) }}</div>
                                </div>
                                <div>
                                    <div class="text-xs text-slate-500">Bawah</div>
                                    <div class="text-lg font-semibold text-slate-900">{{ $n($bawah) }}</div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-lg border border-slate-200 p-4">
                <div class="mb-2 text-sm font-semibold text-slate-800">Printer CS (Pultol)</div>
                <div class="text-sm text-slate-700">{{ $p->printer_css ?? '-' }}</div>
            </div>

            <div class="rounded-lg border border-slate-200 p-4">
                <div class="mb-2 text-sm font-semibold text-slate-800">Printer CSS</div>
                <div class="text-sm text-slate-700">{{ $p->printer_css_2 ?: '-' }}</div>
            </div>
        </div>
    </div>
@endsection