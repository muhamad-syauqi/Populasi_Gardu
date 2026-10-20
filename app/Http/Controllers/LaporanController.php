<?php

namespace App\Http\Controllers;

use App\Models\Gardu;
use App\Models\Gerbang;
use App\Models\PerangkatGardu;
use App\Models\RuasTol;
use App\Models\TipeGardu;
use App\Support\CascadeData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LaporanController extends Controller
{
    private const PALET = ['#2563eb', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#ec4899'];

    private const LABEL_PERANGKAT = [
        'reader_atas' => 'Reader Atas',
        'reader_bawah' => 'Reader Bawah',
        'cctv_lajur' => 'CCTV Lajur',
        'cctv_gandar' => 'CCTV Gandar',
        'lpr_custom' => 'LPR Custom',
        'pyramid_atas' => 'Pyramid Atas',
        'pyramid_bawah' => 'Pyramid Bawah',
        'hs_atas' => 'HS Atas',
        'hs_bawah' => 'HS Bawah',
    ];

    public function index(Request $request): View
    {
        $request->validate([
            'ruas_id' => ['nullable', 'integer'],
            'gerbang_id' => ['nullable', 'integer'],
        ]);

        $ruasId = $request->input('ruas_id');
        $gerbangId = $request->input('gerbang_id');

        $tipeList = TipeGardu::orderBy('id')->get(['id', 'nama_tipe']);

        $gerbangList = Gerbang::query()
            ->when($ruasId, fn ($q) => $q->where('ruas_id', $ruasId))
            ->when($gerbangId, fn ($q) => $q->where('id', $gerbangId))
            ->orderBy('nama_gerbang')
            ->get(['id', 'nama_gerbang']);

        $ids = $gerbangList->pluck('id');

        // Jumlah gardu per gerbang per tipe
        $counts = Gardu::query()
            ->whereIn('gerbang_id', $ids)
            ->selectRaw('gerbang_id, tipe_gardu_id, COUNT(*) as total')
            ->groupBy('gerbang_id', 'tipe_gardu_id')
            ->get()
            ->groupBy('gerbang_id');

        $rows = $gerbangList->map(function ($g) use ($counts, $tipeList) {
            $byTipe = $counts->get($g->id, collect())->pluck('total', 'tipe_gardu_id');

            return [
                'nama' => $g->nama_gerbang,
                'perTipe' => $tipeList->map(fn ($t) => (int) ($byTipe[$t->id] ?? 0))->all(),
                'total' => (int) $byTipe->sum(),
            ];
        });

        // Gardu yang belum punya tipe
        $seriesNames = $tipeList->pluck('nama_tipe')->all();
        if ($rows->contains(fn ($r) => $r['total'] > array_sum($r['perTipe']))) {
            $seriesNames[] = 'Tanpa tipe';
            $rows = $rows->map(function ($r) {
                $r['perTipe'][] = $r['total'] - array_sum($r['perTipe']);

                return $r;
            });
        }

        $warna = fn (int $i, string $nama) => $nama === 'Tanpa tipe'
            ? '#94a3b8'
            : self::PALET[$i % count(self::PALET)];

        $totalGardu = (int) $rows->sum('total');
        $kolom = collect($seriesNames)->keys()
            ->map(fn ($i) => (int) $rows->sum(fn ($r) => $r['perTipe'][$i]))
            ->all();

        $pie = collect($seriesNames)->map(fn ($nama, $i) => [
            'nama' => $nama,
            'jumlah' => $kolom[$i],
            'persen' => $totalGardu > 0 ? round($kolom[$i] / $totalGardu * 100, 1) : 0,
            'warna' => $warna($i, $nama),
        ])->values();

        $judul = $gerbangId
            ? ($gerbangList->first()->nama_gerbang ?? 'Gerbang')
            : ($ruasId ? 'Ruas '.(RuasTol::find($ruasId)->nama_ruas ?? '') : 'Semua Gerbang');

        if ($gerbangId) {
            // Satu gerbang dipilih: rincian jumlah perangkat
            $sum = DB::table('perangkat_gardu')
                ->join('gardu', 'gardu.id', '=', 'perangkat_gardu.gardu_id')
                ->where('gardu.gerbang_id', $gerbangId)
                ->selectRaw(collect(PerangkatGardu::COUNT_FIELDS)
                    ->map(fn ($f) => "COALESCE(SUM(perangkat_gardu.{$f}), 0) as {$f}")
                    ->implode(', '))
                ->first();

            $utama = ['reader_atas', 'reader_bawah', 'cctv_lajur', 'cctv_gandar'];
            $labels = [];
            $values = [];
            foreach (self::LABEL_PERANGKAT as $field => $label) {
                $nilai = (int) ($sum->{$field} ?? 0);
                if ($nilai > 0 || in_array($field, $utama, true)) {
                    $labels[] = $label;
                    $values[] = $nilai;
                }
            }

            $bar = [
                'stacked' => false,
                'title' => 'Perangkat di Gerbang '.$judul,
                'subtitle' => 'Total jumlah perangkat pada seluruh gardu',
                'labels' => $labels,
                'datasets' => [[
                    'label' => 'Jumlah',
                    'data' => $values,
                    'backgroundColor' => '#2563eb',
                    'borderRadius' => 4,
                    'maxBarThickness' => 56,
                ]],
            ];
        } else {
            // Banyak gerbang: gardu per gerbang, dirinci per tipe
            $bar = [
                'stacked' => true,
                'title' => 'Jumlah Gardu per Gerbang',
                'subtitle' => 'Dirinci menurut tipe gardu — '.$judul,
                'labels' => $rows->pluck('nama')->all(),
                'datasets' => collect($seriesNames)->map(fn ($nama, $i) => [
                    'label' => $nama,
                    'data' => $rows->map(fn ($r) => $r['perTipe'][$i])->all(),
                    'backgroundColor' => $warna($i, $nama),
                    'maxBarThickness' => 48,
                ])->values()->all(),
            ];
        }

        return view('laporan.index', [
            'tree' => CascadeData::tree(),
            'ruasId' => $ruasId,
            'gerbangId' => $gerbangId,
            'judul' => $judul,
            'totalGerbang' => $rows->count(),
            'totalGardu' => $totalGardu,
            'totalAktif' => Gardu::whereIn('gerbang_id', $ids)->where('status', 'Aktif')->count(),
            'seriesNames' => $seriesNames,
            'rows' => $rows,
            'kolom' => $kolom,
            'pie' => $pie,
            'bar' => $bar,
        ]);
    }
}