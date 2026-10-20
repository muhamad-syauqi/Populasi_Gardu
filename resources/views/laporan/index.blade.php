@extends('layouts.app')
@section('title', 'Laporan')

@section('content')
    <h1 class="text-2xl font-bold text-slate-900">Laporan Identitas Gardu</h1>
    <p class="mb-4 text-sm text-slate-500">Grafik rincian gardu per gerbang. Pilih ruas atau gerbang untuk melihat detailnya.</p>

    <form method="GET" action="{{ route('laporan.index') }}" class="card mb-6 p-4">
        <div class="grid gap-3 sm:grid-cols-2 lg:max-w-2xl">
            <x-cascade-select :filter="true" :submit="true" :ruas-id="$ruasId" :gerbang-id="$gerbangId" />
        </div>
        @if ($ruasId || $gerbangId)
            <a href="{{ route('laporan.index') }}" class="mt-3 inline-block text-xs text-blue-600 hover:underline">Reset pilihan</a>
        @endif
    </form>
    @include('partials.cascade-data')

    @php
        $stats = [
            ['label' => 'Gerbang', 'value' => $totalGerbang, 'sub' => $judul, 'icon' => 'building', 'color' => 'bg-blue-100 text-blue-600'],
            ['label' => 'Total Gardu', 'value' => $totalGardu, 'sub' => 'Gardu terdaftar', 'icon' => 'grid', 'color' => 'bg-emerald-100 text-emerald-600'],
            ['label' => 'Gardu Aktif', 'value' => $totalAktif, 'sub' => $totalGardu > 0 ? round($totalAktif / $totalGardu * 100).'% dari total' : '-', 'icon' => 'check', 'color' => 'bg-violet-100 text-violet-600'],
        ];
    @endphp

    <div class="grid gap-4 sm:grid-cols-3">
        @foreach ($stats as $s)
            <div class="card flex items-center gap-4 p-5">
                <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl {{ $s['color'] }}">
                    <x-icon :name="$s['icon']" class="h-7 w-7" />
                </span>
                <div class="min-w-0">
                    <div class="text-sm text-slate-500">{{ $s['label'] }}</div>
                    <div class="text-3xl font-bold leading-tight text-slate-900">{{ $s['value'] }}</div>
                    <div class="truncate text-xs text-slate-400">{{ $s['sub'] }}</div>
                </div>
            </div>
        @endforeach
    </div>

    @if ($totalGardu > 0)
        <div class="mt-6 grid gap-4 xl:grid-cols-5">
            <div class="card p-5 xl:col-span-3">
                <h2 class="font-semibold text-slate-800">{{ $bar['title'] }}</h2>
                <p class="mb-4 text-xs text-slate-500">{{ $bar['subtitle'] }}</p>
                <div class="h-80"><canvas id="chartBar"></canvas></div>
            </div>

            <div class="card p-5 xl:col-span-2">
                <h2 class="font-semibold text-slate-800">Komposisi Tipe Gardu</h2>
                <p class="mb-4 text-xs text-slate-500">{{ $judul }}</p>
                <div class="flex flex-col items-center gap-6 sm:flex-row">
                    <div class="h-48 w-48 shrink-0"><canvas id="chartPie"></canvas></div>
                    <ul class="w-full space-y-2 text-sm">
                        @foreach ($pie as $t)
                            <li class="flex items-center justify-between gap-3">
                                <span class="flex items-center gap-2">
                                    <span class="h-2.5 w-2.5 rounded-full" style="background: {{ $t['warna'] }}"></span>
                                    {{ $t['nama'] }}
                                </span>
                                <span class="text-slate-500">{{ $t['jumlah'] }} <span class="text-xs text-slate-400">({{ $t['persen'] }}%)</span></span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

        <div class="card mt-6">
            <h2 class="border-b border-slate-100 p-4 font-semibold text-slate-800">Rekap Gardu per Gerbang</h2>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr>
                            <th class="th w-14">No</th>
                            <th class="th">Gerbang</th>
                            @foreach ($seriesNames as $nama)
                                <th class="th text-center">{{ $nama }}</th>
                            @endforeach
                            <th class="th text-center">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($rows as $r)
                            <tr class="hover:bg-slate-50">
                                <td class="td">{{ $loop->iteration }}</td>
                                <td class="td font-medium text-slate-900">{{ $r['nama'] }}</td>
                                @foreach ($r['perTipe'] as $n)
                                    <td class="td text-center {{ $n === 0 ? 'text-slate-300' : '' }}">{{ $n }}</td>
                                @endforeach
                                <td class="td text-center font-semibold">{{ $r['total'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    @if ($rows->count() > 1)
                        <tfoot>
                            <tr class="bg-slate-50 font-semibold">
                                <td class="td" colspan="2">Total</td>
                                @foreach ($kolom as $n)
                                    <td class="td text-center">{{ $n }}</td>
                                @endforeach
                                <td class="td text-center">{{ $totalGardu }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    @else
        <div class="card mt-6 p-10 text-center text-sm text-slate-400">Belum ada data gardu untuk pilihan ini.</div>
    @endif
@endsection

@if ($totalGardu > 0)
    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
        <script>
            const bar = @json($bar);
            new Chart(document.getElementById('chartBar'), {
                type: 'bar',
                data: { labels: bar.labels, datasets: bar.datasets },
                options: {
                    maintainAspectRatio: false,
                    plugins: { legend: { display: bar.stacked, position: 'bottom' } },
                    scales: {
                        x: { stacked: bar.stacked, grid: { display: false }, ticks: { maxRotation: 60, minRotation: 0 } },
                        y: { stacked: bar.stacked, beginAtZero: true, ticks: { precision: 0 } },
                    },
                },
            });

            new Chart(document.getElementById('chartPie'), {
                type: 'pie',
                data: {
                    labels: @json($pie->pluck('nama')),
                    datasets: [{ data: @json($pie->pluck('jumlah')), backgroundColor: @json($pie->pluck('warna')) }],
                },
                options: { maintainAspectRatio: false, plugins: { legend: { display: false } } },
            });
        </script>
    @endpush
@endif