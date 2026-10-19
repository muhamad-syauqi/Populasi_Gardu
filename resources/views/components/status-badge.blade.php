@props(['status'])
@php
    $cls = match ($status) {
        'Aktif' => 'bg-emerald-100 text-emerald-700',
        'Pemeliharaan' => 'bg-amber-100 text-amber-700',
        default => 'bg-red-100 text-red-600',
    };
@endphp
<span {{ $attributes->class(['inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium', $cls]) }}>{{ $status }}</span>