<?php

namespace App\Http\Controllers;

use App\Support\CascadeData;
use App\Models\Gardu;
use App\Models\Gerbang;
use App\Models\TipeGardu;
use App\Support\XlsxWriter;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LaporanController extends Controller
{
    public function index(Request $request): View
    {
        $this->validateFilters($request);

        return view('laporan.index', [
            'gardu' => $this->query($request)->paginate(15)->withQueryString(),
            'tree' => CascadeData::tree(),
            'tipeList' => TipeGardu::orderBy('id')->get(),
            'filters' => $request->only([
                'ruas_id', 'gerbang_id', 'gardu_id', 'tipe_gardu_id', 'status', 'dari', 'sampai',
            ]),
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $this->validateFilters($request);

        $rows = [[
            'No', 'Ruas Tol', 'Gerbang', 'No Gardu', 'Tipe Gardu', 'Arah', 'Status',
            'Reader Atas', 'Reader Bawah', 'CCTV Lajur', 'CCTV Gandar', 'LPR Custom',
            'Pyramid Atas', 'Pyramid Bawah', 'HS Atas', 'HS Bawah',
            'ALB Tollpro', 'ALB Highspeed', 'ALB GRS', 'ALB Automation',
            'Printer CSS', 'Catatan', 'Tanggal Input',
        ]];

        $ya = fn ($v) => $v ? 'Ya' : 'Tidak';

        foreach ($this->query($request)->get() as $i => $g) {
            $p = $g->perangkat;

            $rows[] = [
                $i + 1,
                $g->gerbang->ruas->nama_ruas ?? '',
                $g->gerbang->nama_gerbang,
                $g->nomor_gardu,
                $g->tipe->nama_tipe ?? '',
                $g->arah ?? '',
                $g->status,
                (int) ($p->reader_atas ?? 0), (int) ($p->reader_bawah ?? 0),
                (int) ($p->cctv_lajur ?? 0), (int) ($p->cctv_gandar ?? 0),
                (int) ($p->lpr_custom ?? 0),
                (int) ($p->pyramid_atas ?? 0), (int) ($p->pyramid_bawah ?? 0),
                (int) ($p->hs_atas ?? 0), (int) ($p->hs_bawah ?? 0),
                $ya($p->alb_tollpro ?? 0), $ya($p->alb_highspeed ?? 0),
                $ya($p->alb_grs ?? 0), $ya($p->alb_automation ?? 0),
                $p->printer_css ?? '',
                $g->catatan ?? '',
                $g->created_at?->format('d/m/Y'),
            ];
        }

        $path = XlsxWriter::build($rows, 'Identitas Gardu');

        return response()
            ->download($path, 'identitas-gardu-'.now()->format('Ymd-His').'.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->deleteFileAfterSend(true);
    }

    private function validateFilters(Request $request): void
    {
        $request->validate([
            'ruas_id' => ['nullable', 'integer'],
            'gerbang_id' => ['nullable', 'integer'],
            'gardu_id' => ['nullable', 'integer'],
            'tipe_gardu_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'in:'.implode(',', Gardu::STATUS)],
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date', 'after_or_equal:dari'],
        ]);
    }

   private function query(Request $request)
    {
        return Gardu::with(['gerbang.ruas', 'tipe', 'perangkat'])
            ->join('gerbang', 'gerbang.id', '=', 'gardu.gerbang_id')
            ->select('gardu.*')
            ->when($request->filled('ruas_id'), fn ($q) => $q->where('gerbang.ruas_id', $request->input('ruas_id')))
            ->when($request->filled('gerbang_id'), fn ($q) => $q->where('gardu.gerbang_id', $request->input('gerbang_id')))
            ->when($request->filled('gardu_id'), fn ($q) => $q->where('gardu.id', $request->input('gardu_id')))
            ->when($request->filled('tipe_gardu_id'), fn ($q) => $q->where('gardu.tipe_gardu_id', $request->input('tipe_gardu_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('gardu.status', $request->input('status')))
            ->when($request->filled('dari'), fn ($q) => $q->whereDate('gardu.created_at', '>=', $request->input('dari')))
            ->when($request->filled('sampai'), fn ($q) => $q->whereDate('gardu.created_at', '<=', $request->input('sampai')))
            ->orderBy('gerbang.nama_gerbang')
            ->orderBy('gardu.nomor_gardu');
    }
}