@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
    <h1 class="text-2xl font-bold text-slate-900">Dashboard</h1>
    <p class="mb-6 text-sm text-slate-500">Selamat datang, {{ auth()->user()->username ?? auth()->user()->name }}! Berikut ringkasan data identitas gardu.</p>

    @php
        $stats = [
            ['label' => 'Total Gerbang', 'value' => $totalGerbang, 'sub' => $totalRuas.' Ruas Tol', 'icon' => 'building', 'color' => 'bg-blue-100 text-blue-600'],
            ['label' => 'Total Gardu', 'value' => $totalGardu, 'sub' => 'Gardu Terdaftar', 'icon' => 'grid', 'color' => 'bg-emerald-100 text-emerald-600'],
            ['label' => 'Tipe Gardu', 'value' => $totalTipe, 'sub' => 'Jenis Tipe', 'icon' => 'document', 'color' => 'bg-violet-100 text-violet-600'],
            ['label' => 'Total Perangkat', 'value' => 7, 'sub' => 'Jenis Perangkat', 'icon' => 'bolt', 'color' => 'bg-amber-100 text-amber-600'],
        ];
    @endphp

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($stats as $s)
            <div class="card flex items-center gap-4 p-5">
                <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl {{ $s['color'] }}">
                    <x-icon :name="$s['icon']" class="h-7 w-7" />
                </span>
                <div>
                    <div class="text-sm text-slate-500">{{ $s['label'] }}</div>
                    <div class="text-3xl font-bold leading-tight text-slate-900">{{ $s['value'] }}</div>
                    <div class="text-xs text-slate-400">{{ $s['sub'] }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-6 grid gap-4 xl:grid-cols-5">
        <div class="card p-5 xl:col-span-3">
            <h2 class="mb-4 font-semibold text-slate-800">Jumlah Gardu per Gerbang</h2>
            @if ($totalGardu > 0)
                <div class="h-72"><canvas id="chartGerbang"></canvas></div>
            @else
                <p class="py-16 text-center text-sm text-slate-400">Belum ada data gardu.</p>
            @endif
        </div>

        <div class="card p-5 xl:col-span-2">
            <h2 class="mb-4 font-semibold text-slate-800">Tipe Gardu</h2>
            @if ($totalGardu > 0)
                <div class="flex flex-col items-center gap-6 sm:flex-row">
                    <div class="h-48 w-48 shrink-0"><canvas id="chartTipe"></canvas></div>
                    <ul class="w-full space-y-2 text-sm">
                        @foreach ($tipe as $t)
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
            @else
                <p class="py-16 text-center text-sm text-slate-400">Belum ada data gardu.</p>
            @endif
        </div>
    </div>
@endsection

@if ($totalGardu > 0)
    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
        <script>
            new Chart(document.getElementById('chartGerbang'), {
                type: 'bar',
                data: {
                    labels: @json($chartGerbang['labels']),
                    datasets: [{
                        data: @json($chartGerbang['values']),
                        backgroundColor: '#2563eb',
                        borderRadius: 4,
                        maxBarThickness: 48,
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { grid: { display: false } } },
                },
            });

            new Chart(document.getElementById('chartTipe'), {
                type: 'pie',
                data: {
                    labels: @json($tipe->pluck('nama')),
                    datasets: [{ data: @json($tipe->pluck('jumlah')), backgroundColor: @json($tipe->pluck('warna')) }],
                },
                options: { maintainAspectRatio: false, plugins: { legend: { display: false } } },
            });
        </script>
    @endpush
@endif