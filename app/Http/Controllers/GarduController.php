<?php

namespace App\Http\Controllers;

use App\Models\Gardu;
use App\Models\Gerbang;
use App\Models\PerangkatGardu;
use App\Models\TipeGardu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GarduController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->input('q'));
        $gerbangId = $request->input('gerbang_id');
        $perPage = in_array((int) $request->input('per_page'), [10, 25, 50, 100], true)
            ? (int) $request->input('per_page') : 10;

        $gardu = Gardu::with(['gerbang', 'tipe'])
            ->join('gerbang', 'gerbang.id', '=', 'gardu.gerbang_id')
            ->select('gardu.*')
            ->when($gerbangId, fn ($query) => $query->where('gardu.gerbang_id', $gerbangId))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('gardu.nomor_gardu', 'like', "%{$q}%")
                        ->orWhere('gardu.status', 'like', "%{$q}%")
                        ->orWhere('gerbang.nama_gerbang', 'like', "%{$q}%")
                        ->orWhereHas('tipe', fn ($t) => $t->where('nama_tipe', 'like', "%{$q}%"));
                });
            })
            ->orderBy('gerbang.nama_gerbang')
            ->orderBy('gardu.nomor_gardu')
            ->paginate($perPage)
            ->withQueryString();

        return view('gardu.index', [
            'gardu' => $gardu,
            'gerbangList' => Gerbang::orderBy('nama_gerbang')->get(['id', 'nama_gerbang']),
            'q' => $q,
            'gerbangId' => $gerbangId,
            'perPage' => $perPage,
        ]);
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

    private function formData(Gardu $gardu): array
    {
        return [
            'gardu' => $gardu,
            'perangkat' => $gardu->perangkat ?? new PerangkatGardu,
            'gerbangList' => Gerbang::with('ruas')->orderBy('nama_gerbang')->get(),
            'tipeList' => TipeGardu::orderBy('id')->get(),
        ];
    }

    private function rules(Request $request, ?Gardu $gardu = null): array
    {
        $rules = [
            'gerbang_id' => ['required', 'exists:gerbang,id'],
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