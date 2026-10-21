<?php

namespace App\Support;

use App\Models\PerangkatGardu;
use Illuminate\Support\Collection;

class GarduExcel
{
    private const WARNA_TIPE = [
        'GTO Single' => 'FFFF00',
        'GTO Mini' => 'FFFF00',
        'GSO M' => 'FFC000',
        'GSO S' => 'FFC000',
        'GTM' => '00B0F0',
    ];

    private array $cells = [];

    private array $merges = [];

    private array $styles = [];

    /**
     * @param  Collection  $gardu  Gardu (dengan gerbang, perangkat) sudah terurut: gerbang lalu nomor
     * @param  Collection  $tipeList  TipeGardu terurut menurut id
     * @return string path file sementara
     */
    public static function build(Collection $gardu, Collection $tipeList, string $namaRuas): string
    {
        return (new self)->make($gardu->values(), $tipeList->values(), $namaRuas);
    }

    private function make(Collection $gardu, Collection $tipeList, string $namaRuas): string
    {
        $printers = PerangkatGardu::PRINTER_OPTIONS;
        $nTipe = $tipeList->count();

        $cNo = 2;
        $cGerbang = 3;
        $cNomor = 4;
        $cArah = 5;
        $cTipe = $cArah + 2;
        $cReader = $cTipe + $nTipe;
        $cCctv = $cReader + 2;
        $cLpr = $cCctv + 2;
        $cAlb = $cLpr + 6;
        $cPrinter = $cAlb + 4;
        $cLast = $cPrinter + count($printers) - 1;

        $rFirst = 6;
        $rLast = $rFirst + max($gardu->count(), 1) - 1;
        $rTotal = $rLast + 1;

        $this->defineBaseStyles();

        // ===== Judul =====
        $this->put(1, $cNo, 'Ruas Tol '.$namaRuas, 'title');
        $this->merge($cNo, 1, $cLast, 1);

        // ===== Header (baris 3-5) =====
        foreach ([$cNo => 'No', $cGerbang => 'Gerbang', $cNomor => 'No Gardu'] as $c => $teks) {
            $this->put(3, $c, $teks, $this->head('92D050'));
            $this->merge($c, 3, $c, 5);
        }

        $this->group(3, $cArah, 2, 'Arah', '92D050');
        $this->column($cArah, 'Entrance', 'A9D08E');
        $this->column($cArah + 1, 'Exit', 'F4B084');

        $this->group(3, $cTipe, $nTipe, 'Tipe Gardu', '92D050');
        foreach ($tipeList as $i => $t) {
            $this->column($cTipe + $i, $t->nama_tipe, self::WARNA_TIPE[$t->nama_tipe] ?? 'D9D9D9');
        }

        $this->group(3, $cReader, 2, 'Jumlah Reader', '92D050');
        $this->column($cReader, 'Atas', '92D050');
        $this->column($cReader + 1, 'Bawah', '92D050');

        $this->group(3, $cCctv, 2, 'CCTV', '92D050');
        $this->column($cCctv, 'Lajur', '92D050');
        $this->column($cCctv + 1, 'Gandar', '92D050');

        $this->group(3, $cLpr, 6, 'LPR', '9DC3E6');
        foreach (['Custom', 'Pyramid', 'HS'] as $i => $jenis) {
            $this->put(4, $cLpr + $i * 2, $jenis, $this->head('9DC3E6'));
            $this->merge($cLpr + $i * 2, 4, $cLpr + $i * 2 + 1, 4);
            $this->put(5, $cLpr + $i * 2, 'Atas', $this->head('9DC3E6'));
            $this->put(5, $cLpr + $i * 2 + 1, 'Bawah', $this->head('9DC3E6'));
        }

        $this->group(3, $cAlb, 4, 'ALB', 'D9C3E8');
        foreach (['Tollpro', 'Highspeed', 'GRS', 'Automation'] as $i => $teks) {
            $this->column($cAlb + $i, $teks, 'D9C3E8');
        }

        $this->group(3, $cPrinter, count($printers), 'Printer CS (Pultol)', 'F8CBAD');
        foreach ($printers as $i => $teks) {
            $this->column($cPrinter + $i, $teks, 'F8CBAD');
        }

        // ===== Data =====
        $sums = array_fill($cArah, $cLast - $cArah + 1, 0);
        $groups = [];

        foreach ($gardu as $i => $g) {
            $r = $rFirst + $i;
            $p = $g->perangkat;

            $lastId = $groups ? $groups[array_key_last($groups)]['id'] : null;
            if ($lastId !== $g->gerbang_id) {
                $groups[] = ['id' => $g->gerbang_id, 'nama' => $g->gerbang->nama_gerbang, 'start' => $r, 'end' => $r];
            } else {
                $groups[array_key_last($groups)]['end'] = $r;
            }

            $this->put($r, $cNomor, (string) $g->nomor_gardu, 'nomor');

            $nilai = [
                $cArah => $g->arah === 'Entrance' ? 1 : 0,
                $cArah + 1 => $g->arah === 'Exit' ? 1 : 0,
            ];
            foreach ($tipeList as $k => $t) {
                $nilai[$cTipe + $k] = ($g->tipe_gardu_id !== null && (int) $g->tipe_gardu_id === (int) $t->id) ? 1 : 0;
            }

            $nilai[$cReader] = (int) ($p->reader_atas ?? 0);
            $nilai[$cReader + 1] = (int) ($p->reader_bawah ?? 0);
            $nilai[$cCctv] = (int) ($p->cctv_lajur ?? 0);
            $nilai[$cCctv + 1] = (int) ($p->cctv_gandar ?? 0);
            $nilai[$cLpr] = (int) ($p->lpr_custom_atas ?? 0);
            $nilai[$cLpr + 1] = (int) ($p->lpr_custom_bawah ?? 0);
            $nilai[$cLpr + 2] = (int) ($p->pyramid_atas ?? 0);
            $nilai[$cLpr + 3] = (int) ($p->pyramid_bawah ?? 0);
            $nilai[$cLpr + 4] = (int) ($p->hs_atas ?? 0);
            $nilai[$cLpr + 5] = (int) ($p->hs_bawah ?? 0);
            $nilai[$cAlb] = (int) ($p->alb_tollpro ?? 0);
            $nilai[$cAlb + 1] = (int) ($p->alb_highspeed ?? 0);
            $nilai[$cAlb + 2] = (int) ($p->alb_grs ?? 0);
            $nilai[$cAlb + 3] = (int) ($p->alb_automation ?? 0);
            foreach ($printers as $k => $nama) {
                $nilai[$cPrinter + $k] = (($p->printer_css ?? null) === $nama) ? 1 : 0;
            }

            foreach ($nilai as $c => $v) {
                $this->put($r, $c, $v > 0 ? $v : null, 'num');
                $sums[$c] += $v;
            }
        }

        foreach ($groups as $n => $grp) {
            $this->put($grp['start'], $cNo, $n + 1, 'no');
            $this->put($grp['start'], $cGerbang, $grp['nama'], 'gerbang');
            if ($grp['end'] > $grp['start']) {
                $this->merge($cNo, $grp['start'], $cNo, $grp['end']);
                $this->merge($cGerbang, $grp['start'], $cGerbang, $grp['end']);
            }
        }

        if ($gardu->isEmpty()) {
            $this->put($rFirst, $cNomor, null, 'nomor');
        }

        // ===== Total =====
        $this->put($rTotal, $cNo, 'TOTAL', 'tot_label');
        $this->merge($cNo, $rTotal, $cNomor, $rTotal);
        for ($c = $cArah; $c <= $cLast; $c++) {
            $this->put($rTotal, $c, $sums[$c], 'tot_num',
                'SUM('.XlsxWriter::columnName($c).$rFirst.':'.XlsxWriter::columnName($c).$rLast.')');
        }

        $rJumlah = $rTotal + 1;
        $this->put($rJumlah, $cNo, 'Total Gardu', 'tot_label');
        $this->merge($cNo, $rJumlah, $cNomor, $rJumlah);
        $this->put($rJumlah, $cTipe, $gardu->count(), 'tot_num',
            'COUNTA('.XlsxWriter::columnName($cNomor).$rFirst.':'.XlsxWriter::columnName($cNomor).$rLast.')');
        $this->merge($cTipe, $rJumlah, $cTipe + $nTipe - 1, $rJumlah);

        // ===== Lebar kolom & tinggi baris =====
        $lebar = [1 => 2.7, $cNo => 6.5, $cGerbang => 20.7, $cNomor => 11];
        for ($c = $cArah; $c <= $cLast; $c++) {
            $lebar[$c] = 10;
        }
        $lebar[$cArah] = 12.5;
        $lebar[$cArah + 1] = 12.5;
        for ($k = 0; $k < $nTipe; $k++) {
            $lebar[$cTipe + $k] = 12.5;
        }
        for ($k = 0; $k < 4; $k++) {
            $lebar[$cAlb + $k] = 13;
        }
        for ($k = 0; $k < count($printers); $k++) {
            $lebar[$cPrinter + $k] = 15;
        }

        return XlsxWriter::build([
            'name' => $namaRuas,
            'zoom' => 85,
            'freezeRows' => 5,
            'landscape' => true,
            'defaultRowHeight' => 18,
            'colWidths' => $lebar,
            'rowHeights' => [1 => 32, 2 => 8, 3 => 21, 4 => 21, 5 => 21],
            'styles' => $this->styles,
            'cells' => $this->cells,
            'merges' => $this->merges,
        ]);
    }

    private function defineBaseStyles(): void
    {
        $this->styles['title'] = ['bold' => true, 'size' => 20, 'h' => 'center', 'v' => 'center'];
        $this->styles['no'] = ['bold' => true, 'size' => 12, 'border' => true, 'h' => 'center', 'v' => 'center'];
        $this->styles['gerbang'] = ['bold' => true, 'size' => 12, 'border' => true, 'h' => 'center', 'v' => 'center', 'wrap' => true];
        $this->styles['nomor'] = ['bold' => true, 'size' => 12, 'border' => true, 'h' => 'center', 'v' => 'center', 'text' => true];
        $this->styles['num'] = ['size' => 12, 'border' => true, 'h' => 'center', 'v' => 'center'];
        $this->styles['tot_label'] = ['bold' => true, 'size' => 12, 'fill' => 'D9D9D9', 'border' => true, 'h' => 'center', 'v' => 'center'];
        $this->styles['tot_num'] = ['bold' => true, 'size' => 12, 'fill' => 'D9D9D9', 'border' => true, 'h' => 'center', 'v' => 'center'];
    }

    /** Style header dengan warna latar tertentu. */
    private function head(string $fill): string
    {
        $key = 'h_'.$fill;
        if (! isset($this->styles[$key])) {
            $this->styles[$key] = [
                'bold' => true, 'size' => 12, 'fill' => $fill,
                'border' => true, 'h' => 'center', 'v' => 'center', 'wrap' => true,
            ];
        }

        return $key;
    }

    /** Judul kelompok pada baris $row yang membentang $n kolom. */
    private function group(int $row, int $col, int $n, string $title, string $fill): void
    {
        $this->put($row, $col, $title, $this->head($fill));
        if ($n > 1) {
            $this->merge($col, $row, $col + $n - 1, $row);
        }
    }

    /** Judul kolom yang membentang baris 4-5. */
    private function column(int $col, string $title, string $fill): void
    {
        $this->put(4, $col, $title, $this->head($fill));
        $this->merge($col, 4, $col, 5);
    }

    private function put(int $row, int $col, $value, string $style, ?string $formula = null): void
    {
        $this->cells[] = [$row, $col, $value, $style, $formula];
    }

    private function merge(int $c1, int $r1, int $c2, int $r2): void
    {
        $this->merges[] = XlsxWriter::columnName($c1).$r1.':'.XlsxWriter::columnName($c2).$r2;
    }
}