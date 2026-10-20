<?php

namespace App\Http\Controllers;

use App\Models\Gardu;
use App\Models\Gerbang;
use App\Models\RuasTol;
use App\Models\TipeGardu;
use App\Support\CascadeData;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LaporanController extends Controller
{
    private const PALET = ['#2563eb', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#ec4899'];

    private const METRIK = [
        'tipe' => 'Tipe Gardu',
        'semua' => 'Semua Perangkat',
        'reader' => 'Jumlah Reader',
        'cctv' => 'CCTV',
        'lpr' => 'LPR',
        'alb' => 'ALB',
        'printer' => 'Printer CSS',
    ];

    private const SUBJUDUL = [
        'tipe' => 'Jumlah gardu menurut tipe',
        'semua' => 'Reader, CCTV, LPR (Custom, Pyramid, HS), ALB, dan Printer CSS (ALB dan Printer CSS dihitung per gardu)',
        'reader' => 'Jumlah reader pada seluruh gardu',
        'cctv' => 'Jumlah CCTV pada seluruh gardu',
        'lpr' => 'Jumlah LPR (Custom, Pyramid, dan HS) pada seluruh gardu',        'alb' => 'Jumlah gardu yang memiliki tiap jenis ALB',
        'printer' => 'Jumlah gardu menurut tipe printer CSS',
    ];

    /** Kolom perangkat_gardu untuk tiap metrik berjenis jumlah. */
       /** Jenis per metrik: label => kolom perangkat_gardu yang dijumlahkan. */
    private const KOLOM = [
        'reader' => ['Atas' => ['reader_atas'], 'Bawah' => ['reader_bawah']],
        'cctv' => ['Lajur' => ['cctv_lajur'], 'Gandar' => ['cctv_gandar']],
        'lpr' => [
            'Custom' => ['lpr_custom'],
            'Pyramid' => ['pyramid_atas', 'pyramid_bawah'],
            'HS' => ['hs_atas', 'hs_bawah'],
        ],
        'alb' => [
            'Tollpro' => ['alb_tollpro'],
            'Highspeed' => ['alb_highspeed'],
            'GRS' => ['alb_grs'],
            'Automation' => ['alb_automation'],
        ],
    ];

    private const ABU = ['Tanpa tipe', 'Belum diisi'];

    private const SEMUA = [
        'Reader Atas' => ['reader_atas'],
        'Reader Bawah' => ['reader_bawah'],
        'CCTV Lajur' => ['cctv_lajur'],
        'CCTV Gandar' => ['cctv_gandar'],
        'LPR Custom' => ['lpr_custom'],
        'LPR Pyramid' => ['pyramid_atas', 'pyramid_bawah'],
        'LPR HS' => ['hs_atas', 'hs_bawah'],
        'ALB Tollpro' => ['alb_tollpro'],
        'ALB Highspeed' => ['alb_highspeed'],
        'ALB GRS' => ['alb_grs'],
        'ALB Automation' => ['alb_automation'],
    ];

    private const PALET_SEMUA = [
        '#1d4ed8', '#60a5fa',            // Reader
        '#059669', '#6ee7b7',            // CCTV
        '#f59e0b', '#b45309', '#fcd34d', // LPR (Custom, Pyramid, HS)
        '#7c3aed', '#a78bfa', '#db2777', '#f472b6', // ALB
        '#0891b2',                       // Printer CSS
    ];

    public function index(Request $request): View
    {
        $request->validate([
            'ruas_id' => ['nullable', 'integer'],
            'gerbang_id' => ['nullable', 'integer'],
            'perangkat' => ['nullable', 'in:'.implode(',', array_keys(self::METRIK))],
        ]);

        $ruasId = $request->input('ruas_id');
        $gerbangId = $request->input('gerbang_id');
        $metrik = $request->input('perangkat') ?: 'tipe';
        $labelMetrik = self::METRIK[$metrik];

        $gerbangList = Gerbang::query()
            ->when($ruasId, fn ($q) => $q->where('ruas_id', $ruasId))
            ->when($gerbangId, fn ($q) => $q->where('id', $gerbangId))
            ->orderBy('nama_gerbang')
            ->get(['id', 'nama_gerbang']);

        $ids = $gerbangList->pluck('id');

        [$seriesNames, $rows] = match ($metrik) {
            'tipe' => $this->perTipe($gerbangList, $ids),
            'semua' => $this->perSemua($gerbangList, $ids),
            'printer' => $this->perPrinter($gerbangList, $ids),
            default => $this->perKolom(self::KOLOM[$metrik], $gerbangList, $ids),
        };

        $warna = fn (int $i, string $nama) => in_array($nama, self::ABU, true)
            ? '#94a3b8'
            : ($metrik === 'semua'
                ? self::PALET_SEMUA[$i % count(self::PALET_SEMUA)]
                : self::PALET[$i % count(self::PALET)]);

        $kolom = collect($seriesNames)->keys()
            ->map(fn ($i) => (int) $rows->sum(fn ($r) => $r['values'][$i]))
            ->all();
        $totalMetrik = array_sum($kolom);

        $pie = collect($seriesNames)->map(fn ($nama, $i) => [
            'nama' => $nama,
            'jumlah' => $kolom[$i],
            'persen' => $totalMetrik > 0 ? round($kolom[$i] / $totalMetrik * 100, 1) : 0,
            'warna' => $warna($i, $nama),
        ])->values();

        $judul = $gerbangId
            ? ($gerbangList->first()->nama_gerbang ?? 'Gerbang')
            : ($ruasId ? 'Ruas '.(RuasTol::find($ruasId)->nama_ruas ?? '') : 'Semua Gerbang');

        if ($gerbangId) {
            // Satu gerbang dipilih: satu batang per jenis
            $bar = [
                'stacked' => false,
                'title' => $labelMetrik.' di Gerbang '.$judul,
                'subtitle' => self::SUBJUDUL[$metrik],
                'labels' => $seriesNames,
                'datasets' => [[
                    'label' => 'Jumlah',
                    'data' => $kolom,
                    'backgroundColor' => $pie->pluck('warna')->all(),
                    'borderRadius' => 4,
                    'maxBarThickness' => 56,
                ]],
            ];
        } else {
            // Banyak gerbang: satu batang per gerbang, ditumpuk per jenis
            $bar = [
                'stacked' => true,
                'title' => $metrik === 'tipe' ? 'Jumlah Gardu per Gerbang' : $labelMetrik.' per Gerbang',
                'subtitle' => self::SUBJUDUL[$metrik].' — '.$judul,
                'labels' => $rows->pluck('nama')->all(),
                'datasets' => collect($seriesNames)->map(fn ($nama, $i) => [
                    'label' => $nama,
                    'data' => $rows->map(fn ($r) => $r['values'][$i])->all(),
                    'backgroundColor' => $warna($i, $nama),
                    'maxBarThickness' => 48,
                ])->values()->all(),
            ];
        }

        return view('laporan.index', [
            'tree' => CascadeData::tree(),
            'ruasId' => $ruasId,
            'gerbangId' => $gerbangId,
            'metrik' => $metrik,
            'metrikList' => self::METRIK,
            'labelMetrik' => $labelMetrik,
            'judul' => $judul,
            'totalGerbang' => $rows->count(),
            'totalGardu' => Gardu::whereIn('gerbang_id', $ids)->count(),
            'totalAktif' => Gardu::whereIn('gerbang_id', $ids)->where('status', 'Aktif')->count(),
            'totalMetrik' => $totalMetrik,
            'seriesNames' => $seriesNames,
            'rows' => $rows,
            'kolom' => $kolom,
            'pie' => $pie,
            'bar' => $bar,
        ]);
    }

    /** Jumlah gardu per gerbang per tipe gardu. */
    private function perTipe(Collection $gerbangList, Collection $ids): array
    {
        $tipeList = TipeGardu::orderBy('id')->get(['id', 'nama_tipe']);

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
                'values' => $tipeList->map(fn ($t) => (int) ($byTipe[$t->id] ?? 0))->all(),
                'semua' => (int) $byTipe->sum(),
            ];
        });


        $names = $tipeList->pluck('nama_tipe')->all();

        // Gardu yang belum punya tipe
        if ($rows->contains(fn ($r) => $r['semua'] > array_sum($r['values']))) {
            $names[] = 'Tanpa tipe';
            $rows = $rows->map(function ($r) {
                $r['values'][] = $r['semua'] - array_sum($r['values']);

                return $r;
            });
        }

        return [
            $names,
            $rows->map(fn ($r) => [
                'nama' => $r['nama'],
                'values' => $r['values'],
                'total' => array_sum($r['values']),
            ])->values(),
        ];
    }


    /** Jumlah (SUM) kolom perangkat per gerbang: reader, CCTV, LPR, ALB. */
        /** Jumlah (SUM) kolom perangkat per gerbang. $kolom: label => [kolom yang dijumlahkan]. */
    private function perKolom(array $kolom, Collection $gerbangList, Collection $ids): array
    {
        $fields = collect($kolom)->flatten()->unique()->values();

        $sum = DB::table('perangkat_gardu')
            ->join('gardu', 'gardu.id', '=', 'perangkat_gardu.gardu_id')
            ->whereIn('gardu.gerbang_id', $ids)
            ->groupBy('gardu.gerbang_id')
            ->selectRaw('gardu.gerbang_id, '.$fields
                ->map(fn ($f) => "COALESCE(SUM(perangkat_gardu.{$f}), 0) as {$f}")
                ->implode(', '))
            ->get()
            ->keyBy('gerbang_id');

        $rows = $gerbangList->map(function ($g) use ($sum, $kolom) {
            $r = $sum->get($g->id);

            $values = array_map(
                fn (array $cols) => array_sum(array_map(fn ($c) => (int) ($r->{$c} ?? 0), $cols)),
                array_values($kolom)
            );

            return ['nama' => $g->nama_gerbang, 'values' => $values, 'total' => array_sum($values)];
        });

        return [array_keys($kolom), $rows->values()];
    }

        /** Semua perangkat sekaligus: reader, CCTV, LPR, ALB, dan jumlah gardu ber-printer CSS. */
    private function perSemua(Collection $gerbangList, Collection $ids): array
    {
        [$names, $rows] = $this->perKolom(self::SEMUA, $gerbangList, $ids);

        $printer = DB::table('gardu')
            ->join('perangkat_gardu', 'perangkat_gardu.gardu_id', '=', 'gardu.id')
            ->whereIn('gardu.gerbang_id', $ids)
            ->whereNotNull('perangkat_gardu.printer_css')
            ->where('perangkat_gardu.printer_css', '<>', '')
            ->selectRaw('gardu.gerbang_id, COUNT(*) as total')
            ->groupBy('gardu.gerbang_id')
            ->pluck('total', 'gerbang_id');

        $names[] = 'Printer CSS';
        $gerbangIds = $gerbangList->pluck('id')->values();

        $rows = $rows->map(function ($r, $i) use ($printer, $gerbangIds) {
            $r['values'][] = (int) ($printer[$gerbangIds[$i]] ?? 0);
            $r['total'] = array_sum($r['values']);

            return $r;
        })->values();

        return [$names, $rows];
    }

    /** Jumlah gardu per gerbang menurut tipe printer CSS. */
    private function perPrinter(Collection $gerbangList, Collection $ids): array
    {
        $kosong = 'Belum diisi';
        $nama = function ($v) use ($kosong) {
            $v = trim((string) $v);

            return $v === '' ? $kosong : $v;
        };

        $data = DB::table('gardu')
            ->leftJoin('perangkat_gardu', 'perangkat_gardu.gardu_id', '=', 'gardu.id')
            ->whereIn('gardu.gerbang_id', $ids)
            ->get(['gardu.gerbang_id', 'perangkat_gardu.printer_css']);

        $names = $data->map(fn ($d) => $nama($d->printer_css))
            ->reject(fn ($n) => $n === $kosong)
            ->unique()->sort()->values()->all();

        if ($data->contains(fn ($d) => $nama($d->printer_css) === $kosong)) {
            $names[] = $kosong;
        }

        $byGerbang = $data->groupBy('gerbang_id');

        $rows = $gerbangList->map(function ($g) use ($byGerbang, $names, $nama) {
            $items = $byGerbang->get($g->id, collect());
            $values = array_map(
                fn ($n) => $items->filter(fn ($d) => $nama($d->printer_css) === $n)->count(),
                $names
            );

            return ['nama' => $g->nama_gerbang, 'values' => $values, 'total' => array_sum($values)];
        });

        return [$names, $rows->values()];
    }
}