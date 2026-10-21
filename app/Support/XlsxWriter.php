<?php

namespace App\Support;

use RuntimeException;
use ZipArchive;

/**
 * Penulis .xlsx sederhana (tanpa paket tambahan, hanya butuh ext-zip).
 *
 * $spec = [
 *   'name'             => 'Sheet1',
 *   'zoom'             => 85,
 *   'freezeRows'       => 5,
 *   'landscape'        => true,
 *   'defaultRowHeight' => 18,
 *   'colWidths'        => [2 => 6.5],        // nomor kolom (A=1) => lebar
 *   'rowHeights'       => [1 => 30],         // nomor baris => tinggi
 *   'styles'           => ['key' => ['bold'=>true,'size'=>12,'color'=>'000000','fill'=>'92D050',
 *                                    'border'=>true,'h'=>'center','v'=>'center','wrap'=>true,'text'=>true]],
 *   'cells'            => [[baris, kolom, nilai, 'key', 'rumus'|null], ...],
 *   'merges'           => ['B1:K1', ...],    // sel kosong di dalam merge otomatis memakai style sel kiri-atas
 * ]
 */
class XlsxWriter
{
    /** @return string path file sementara */
    public static function build(array $spec): string
    {
        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Tidak dapat membuat file Excel.');
        }

        $ids = [];
        $stylesXml = self::stylesXml($spec['styles'] ?? [], $ids);

        $name = preg_replace('/[\\\\\/\?\*\[\]:]/', ' ', (string) ($spec['name'] ?? 'Sheet1'));
        $name = self::escape(mb_substr($name, 0, 31));

        $zip->addFromString('[Content_Types].xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'</Types>');

        $zip->addFromString('_rels/.rels',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>');

        $zip->addFromString('xl/workbook.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="'.$name.'" sheetId="1" r:id="rId1"/></sheets>'
            .'<calcPr fullCalcOnLoad="1"/>'
            .'</workbook>');

        $zip->addFromString('xl/_rels/workbook.xml.rels',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>');

        $zip->addFromString('xl/styles.xml', $stylesXml);
        $zip->addFromString('xl/worksheets/sheet1.xml', self::sheetXml($spec, $ids));
        $zip->close();

        return $path;
    }

    /** Nomor kolom (A=1) menjadi huruf: 1 => A, 27 => AA. */
    public static function columnName(int $index): string
    {
        $name = '';
        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $name = chr(65 + $mod).$name;
            $index = intdiv($index - $mod - 1, 26);
        }

        return $name;
    }

    private static function columnIndex(string $letters): int
    {
        $n = 0;
        foreach (str_split($letters) as $ch) {
            $n = $n * 26 + (ord($ch) - 64);
        }

        return $n;
    }

    private static function stylesXml(array $styles, array &$ids): string
    {
        $fonts = ['<font><sz val="11"/><name val="Calibri"/></font>'];
        $fills = [
            '<fill><patternFill patternType="none"/></fill>',
            '<fill><patternFill patternType="gray125"/></fill>',
        ];
        $borders = [
            '<border><left/><right/><top/><bottom/><diagonal/></border>',
            '<border><left style="thin"><color auto="1"/></left><right style="thin"><color auto="1"/></right>'
            .'<top style="thin"><color auto="1"/></top><bottom style="thin"><color auto="1"/></bottom><diagonal/></border>',
        ];
        $xfs = ['<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'];

        foreach ($styles as $key => $s) {
            $font = '<font>'.(! empty($s['bold']) ? '<b/>' : '')
                .'<sz val="'.($s['size'] ?? 11).'"/>'
                .(isset($s['color']) ? '<color rgb="FF'.$s['color'].'"/>' : '')
                .'<name val="Calibri"/></font>';
            $fontId = self::register($fonts, $font);

            $fillId = 0;
            if (! empty($s['fill'])) {
                $fillId = self::register($fills,
                    '<fill><patternFill patternType="solid"><fgColor rgb="FF'.$s['fill'].'"/><bgColor indexed="64"/></patternFill></fill>');
            }

            $borderId = ! empty($s['border']) ? 1 : 0;
            $numFmt = ! empty($s['text']) ? 49 : 0;

            $align = '<alignment'
                .(isset($s['h']) ? ' horizontal="'.$s['h'].'"' : '')
                .(isset($s['v']) ? ' vertical="'.$s['v'].'"' : '')
                .(! empty($s['wrap']) ? ' wrapText="1"' : '')
                .'/>';

            $xfs[] = '<xf numFmtId="'.$numFmt.'" fontId="'.$fontId.'" fillId="'.$fillId.'" borderId="'.$borderId.'" xfId="0"'
                .' applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">'.$align.'</xf>';
            $ids[$key] = count($xfs) - 1;
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="'.count($fonts).'">'.implode('', $fonts).'</fonts>'
            .'<fills count="'.count($fills).'">'.implode('', $fills).'</fills>'
            .'<borders count="'.count($borders).'">'.implode('', $borders).'</borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="'.count($xfs).'">'.implode('', $xfs).'</cellXfs>'
            .'<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            .'</styleSheet>';
    }

    private static function register(array &$list, string $xml): int
    {
        $i = array_search($xml, $list, true);
        if ($i === false) {
            $list[] = $xml;

            return count($list) - 1;
        }

        return $i;
    }

    private static function sheetXml(array $spec, array $ids): string
    {
        // 1. kumpulkan sel: $grid[baris][kolom]
        $grid = [];
        foreach ($spec['cells'] ?? [] as $cell) {
            $grid[$cell[0]][$cell[1]] = [
                'v' => $cell[2],
                's' => $cell[3] ?? null,
                'f' => $cell[4] ?? null,
            ];
        }

        // 2. sel kosong di dalam merge diberi style sel kiri-atas (agar border/warna merata)
        foreach ($spec['merges'] ?? [] as $range) {
            [$a, $b] = explode(':', $range);
            preg_match('/^([A-Z]+)(\d+)$/', $a, $ma);
            preg_match('/^([A-Z]+)(\d+)$/', $b, $mb);
            $c1 = self::columnIndex($ma[1]);
            $r1 = (int) $ma[2];
            $c2 = self::columnIndex($mb[1]);
            $r2 = (int) $mb[2];
            $style = $grid[$r1][$c1]['s'] ?? null;

            for ($r = $r1; $r <= $r2; $r++) {
                for ($c = $c1; $c <= $c2; $c++) {
                    if (! isset($grid[$r][$c])) {
                        $grid[$r][$c] = ['v' => null, 's' => $style, 'f' => null];
                    }
                }
            }
        }

        $heights = $spec['rowHeights'] ?? [];
        foreach (array_keys($heights) as $r) {
            if (! isset($grid[$r])) {
                $grid[$r] = [];
            }
        }

        ksort($grid);

        $maxCol = 1;
        $maxRow = 1;
        $rowsXml = '';

        foreach ($grid as $r => $cols) {
            ksort($cols);
            $maxRow = max($maxRow, $r);
            $rowsXml .= '<row r="'.$r.'"'.(isset($heights[$r]) ? ' ht="'.$heights[$r].'" customHeight="1"' : '').'>';

            foreach ($cols as $c => $cell) {
                $maxCol = max($maxCol, $c);
                $ref = self::columnName($c).$r;
                $sAttr = ($cell['s'] !== null) ? ' s="'.$ids[$cell['s']].'"' : '';
                $v = $cell['v'];

                if ($cell['f'] !== null) {
                    $rowsXml .= '<c r="'.$ref.'"'.$sAttr.'><f>'.self::escape($cell['f']).'</f><v>'.$v.'</v></c>';
                } elseif ($v === null || $v === '') {
                    $rowsXml .= '<c r="'.$ref.'"'.$sAttr.'/>';
                } elseif (is_int($v) || is_float($v)) {
                    $rowsXml .= '<c r="'.$ref.'"'.$sAttr.'><v>'.$v.'</v></c>';
                } else {
                    $rowsXml .= '<c r="'.$ref.'"'.$sAttr.' t="inlineStr"><is><t xml:space="preserve">'
                        .self::escape((string) $v).'</t></is></c>';
                }
            }

            $rowsXml .= '</row>';
        }

        // 3. kolom
        $colsXml = '';
        $widths = $spec['colWidths'] ?? [];
        ksort($widths);
        foreach ($widths as $c => $w) {
            $colsXml .= '<col min="'.$c.'" max="'.$c.'" width="'.$w.'" customWidth="1"/>';
        }

        // 4. tampilan (zoom + freeze baris)
        $zoom = (int) ($spec['zoom'] ?? 100);
        $pane = '';
        if (! empty($spec['freezeRows'])) {
            $n = (int) $spec['freezeRows'];
            $top = 'A'.($n + 1);
            $pane = '<pane ySplit="'.$n.'" topLeftCell="'.$top.'" activePane="bottomLeft" state="frozen"/>'
                .'<selection pane="bottomLeft" activeCell="'.$top.'" sqref="'.$top.'"/>';
        }

        $merges = $spec['merges'] ?? [];
        $mergeXml = '';
        if ($merges) {
            $mergeXml = '<mergeCells count="'.count($merges).'">';
            foreach ($merges as $m) {
                $mergeXml .= '<mergeCell ref="'.$m.'"/>';
            }
            $mergeXml .= '</mergeCells>';
        }

        $landscape = ! empty($spec['landscape']);

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetPr><pageSetUpPr fitToPage="1"/></sheetPr>'
            .'<dimension ref="A1:'.self::columnName($maxCol).$maxRow.'"/>'
            .'<sheetViews><sheetView tabSelected="1" workbookViewId="0" zoomScale="'.$zoom.'" zoomScaleNormal="'.$zoom.'">'.$pane.'</sheetView></sheetViews>'
            .'<sheetFormatPr defaultRowHeight="'.($spec['defaultRowHeight'] ?? 15).'" customHeight="1"/>'
            .($colsXml !== '' ? '<cols>'.$colsXml.'</cols>' : '')
            .'<sheetData>'.$rowsXml.'</sheetData>'
            .$mergeXml
            .'<pageMargins left="0.4" right="0.4" top="0.5" bottom="0.5" header="0.3" footer="0.3"/>'
            .'<pageSetup paperSize="9" orientation="'.($landscape ? 'landscape' : 'portrait').'" fitToWidth="1" fitToHeight="0"/>'
            .'</worksheet>';
    }

    private static function escape(string $value): string
    {
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $value) ?? '';

        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}