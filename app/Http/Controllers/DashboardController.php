<?php

namespace App\Http\Controllers;

use App\Models\Gardu;
use App\Models\Gerbang;
use App\Models\RuasTol;
use App\Models\TipeGardu;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $totalGardu = Gardu::count();

        $perGerbang = Gerbang::withCount('gardu')->orderBy('nama_gerbang')->get(['id', 'nama_gerbang']);
        $perTipe = TipeGardu::withCount('gardu')->orderBy('id')->get(['id', 'nama_tipe']);

        $warna = ['#2563eb', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#ec4899'];

        $tipe = $perTipe->values()->map(fn ($t, $i) => [
            'nama' => $t->nama_tipe,
            'jumlah' => $t->gardu_count,
            'persen' => $totalGardu > 0 ? round($t->gardu_count / $totalGardu * 100, 1) : 0,
            'warna' => $warna[$i % count($warna)],
        ]);

        return view('dashboard', [
            'totalGerbang' => $perGerbang->count(),
            'totalRuas' => RuasTol::count(),
            'totalGardu' => $totalGardu,
            'totalTipe' => $perTipe->count(),
            'chartGerbang' => [
                'labels' => $perGerbang->pluck('nama_gerbang')->all(),
                'values' => $perGerbang->pluck('gardu_count')->all(),
            ],
            'tipe' => $tipe,
        ]);
    }
}