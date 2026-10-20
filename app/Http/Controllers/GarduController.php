<?php

namespace App\Http\Controllers;

use App\Models\Gardu;
use App\Models\Gerbang;
use App\Models\PerangkatGardu;
use App\Models\TipeGardu;
use App\Support\CascadeData;
use App\Support\XlsxWriter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class GarduController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = in_array((int) $request->input('per_page'), [10, 25, 50, 100], true)
            ? (int) $request->input('per_page') : 10;

        return view('gardu.index', [
            'gardu' => $this->filteredQuery($request)->paginate($perPage)->withQueryString(),
            'tree' => CascadeData::tree(),
            'q' => trim((string) $request->input('q')),
            'ruasId' => $request->input('ruas_id'),
            'gerbangId' => $request->input('gerbang_id'),
            'garduId' => $request->input('gardu_id'),
            'perPage' => $perPage,
        ]);
    }

    /** Download Excel: mengikuti filter yang sedang aktif di halaman Data Gardu. */
    public function export(Request $request): BinaryFileResponse
    {
        $rows = [[
            'No', 'Ruas Tol', 'Gerbang', 'No Gardu', 'Tipe Gardu', 'Arah', 'Status',
            'Reader Atas', 'Reader Bawah', 'CCTV Lajur', 'CCTV Gandar', 'LPR Custom',
            'Pyramid Atas', 'Pyramid Bawah', 'HS Atas', 'HS Bawah',
            'ALB Tollpro', 'ALB Highspeed', 'ALB GRS', 'ALB Automation',
            'Printer CSS', 'Catatan', 'Tanggal Input',
        ]];

        $ya = fn ($v) => $v ? 'Ya' : 'Tidak';

        $data = $this->filteredQuery($request)->with(['gerbang.ruas', 'perangkat'])->get();

        foreach ($data as $i => $g) {
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

    public function create(): View
    {
        return view('gardu.form', $this->formData(new Gardu(['status' => 'Aktif'])));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules($request));

        DB::transaction(function () use ($data, $request) {
            $gardu = Gardu::create($this->garduAttributes($data));
            $gardu->perangkat()->create($this->perangkatAttributes($request));
        });

        return redirect()->route('gardu.index')->with('success', 'Data gardu berhasil ditambahkan.');
    }

    public function show(Gardu $gardu): View
    {
        $gardu->load(['gerbang.ruas', 'tipe', 'perangkat']);

        return view('gardu.show', compact('gardu'));
    }

    public function edit(Gardu $gardu): View
    {
        $gardu->load('perangkat');

        return view('gardu.form', $this->formData($gardu));
    }

    public function update(Request $request, Gardu $gardu): RedirectResponse
    {
        $data = $request->validate($this->rules($request, $gardu));

        DB::transaction(function () use ($data, $request, $gardu) {
            $gardu->update($this->garduAttributes($data));
            $gardu->perangkat()->updateOrCreate([], $this->perangkatAttributes($request));
        });

        return redirect()->route('gardu.show', $gardu)->with('success', 'Data gardu berhasil diperbarui.');
    }

    public function destroy(Gardu $gardu): RedirectResponse
    {
        $gardu->delete(); // perangkat_gardu ikut terhapus (cascade)

        return redirect()->route('gardu.index')->with('success', 'Data gardu berhasil dihapus.');
    }

    /** Query daftar gardu + filter (dipakai halaman daftar dan download Excel). */
    private function filteredQuery(Request $request)
    {
        $q = trim((string) $request->input('q'));
        $ruasId = $request->input('ruas_id');
        $gerbangId = $request->input('gerbang_id');
        $garduId = $request->input('gardu_id');

        return Gardu::with(['gerbang', 'tipe'])
            ->join('gerbang', 'gerbang.id', '=', 'gardu.gerbang_id')
            ->select('gardu.*')
            ->when($ruasId, fn ($query) => $query->where('gerbang.ruas_id', $ruasId))
            ->when($gerbangId, fn ($query) => $query->where('gardu.gerbang_id', $gerbangId))
            ->when($garduId, fn ($query) => $query->where('gardu.id', $garduId))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('gardu.nomor_gardu', 'like', "%{$q}%")
                        ->orWhere('gardu.status', 'like', "%{$q}%")
                        ->orWhere('gerbang.nama_gerbang', 'like', "%{$q}%")
                        ->orWhereHas('tipe', fn ($t) => $t->where('nama_tipe', 'like', "%{$q}%"));
                });
            })
            ->orderBy('gerbang.nama_gerbang')
            ->orderBy('gardu.nomor_gardu');
    }

    private function formData(Gardu $gardu): array
    {
        return [
            'gardu' => $gardu,
            'perangkat' => $gardu->perangkat ?? new PerangkatGardu,
            'tree' => CascadeData::tree(),
            'tipeList' => TipeGardu::orderBy('id')->get(),
        ];
    }

    private function rules(Request $request, ?Gardu $gardu = null): array
    {
        $rules = [
            'ruas_id' => ['nullable', 'exists:ruas_tol,id'],
            'gerbang_id' => [
                'required', 'exists:gerbang,id',
                function ($attribute, $value, $fail) use ($request) {
                    $ruasId = $request->input('ruas_id');
                    if ($ruasId && ! Gerbang::where('id', $value)->where('ruas_id', $ruasId)->exists()) {
                        $fail('Gerbang tidak sesuai dengan ruas tol yang dipilih.');
                    }
                },
            ],
            'nomor_gardu' => [
                'required', 'string', 'max:10',
                Rule::unique('gardu', 'nomor_gardu')
                    ->where('gerbang_id', $request->input('gerbang_id'))
                    ->ignore($gardu?->id),
            ],
            'tipe_gardu_id' => ['nullable', 'exists:tipe_gardu,id'],
            'arah' => ['nullable', Rule::in(Gardu::ARAH)],
            'status' => ['required', Rule::in(Gardu::STATUS)],
            'catatan' => ['nullable', 'string', 'max:2000'],
            'printer_css' => ['nullable', 'string', 'max:255'],
        ];

        foreach (PerangkatGardu::COUNT_FIELDS as $field) {
            $rules[$field] = ['nullable', 'integer', 'min:0', 'max:99'];
        }

        foreach (array_keys(PerangkatGardu::ALB_FIELDS) as $field) {
            $rules[$field] = ['nullable', 'boolean'];
        }

        return $rules;
    }

    private function garduAttributes(array $data): array
    {
        return [
            'gerbang_id' => $data['gerbang_id'],
            'nomor_gardu' => $data['nomor_gardu'],
            'tipe_gardu_id' => $data['tipe_gardu_id'] ?? null,
            'arah' => $data['arah'] ?? null,
            'status' => $data['status'],
            'catatan' => $data['catatan'] ?? null,
        ];
    }

    private function perangkatAttributes(Request $request): array
    {
        $attrs = [];

        foreach (PerangkatGardu::COUNT_FIELDS as $field) {
            $attrs[$field] = (int) $request->input($field, 0);
        }

        foreach (array_keys(PerangkatGardu::ALB_FIELDS) as $field) {
            $attrs[$field] = $request->boolean($field) ? 1 : 0;
        }

        $attrs['printer_css'] = $request->input('printer_css') ?: null;

        return $attrs;
    }
}