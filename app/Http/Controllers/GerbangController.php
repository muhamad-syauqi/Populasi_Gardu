<?php

namespace App\Http\Controllers;

use App\Models\Gerbang;
use App\Models\RuasTol;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GerbangController extends Controller
{
    /** Nilai dropdown ruas yang berarti "input ruas baru". */
    private const RUAS_BARU = 'baru';

    public function index(Request $request): View
    {
        $q = trim((string) $request->input('q'));
        $perPage = in_array((int) $request->input('per_page'), [10, 25, 50, 100], true)
            ? (int) $request->input('per_page') : 10;

        $gerbang = Gerbang::with('ruas')
            ->withCount('gardu')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('nama_gerbang', 'like', "%{$q}%")
                        ->orWhere('lokasi', 'like', "%{$q}%")
                        ->orWhereHas('ruas', fn ($r) => $r->where('nama_ruas', 'like', "%{$q}%"));
                });
            })
            ->orderBy('nama_gerbang')
            ->paginate($perPage)
            ->withQueryString();

        return view('gerbang.index', compact('gerbang', 'q', 'perPage'));
    }

    public function create(): View
    {
        return view('gerbang.form', [
            'gerbang' => new Gerbang,
            'ruas' => RuasTol::orderBy('nama_ruas')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        DB::transaction(fn () => Gerbang::create($this->validated($request)));
        return redirect()->route('gerbang.index')->with('success', 'Gerbang berhasil ditambahkan.');
    }

    public function edit(Gerbang $gerbang): View
    {
        return view('gerbang.form', [
            'gerbang' => $gerbang,
            'ruas' => RuasTol::orderBy('nama_ruas')->get(),
        ]);
    }

    public function update(Request $request, Gerbang $gerbang): RedirectResponse
    {
        DB::transaction(fn () => $gerbang->update($this->validated($request, $gerbang)));
        return redirect()->route('gerbang.index')->with('success', 'Gerbang berhasil diperbarui.');
    }

    public function destroy(Gerbang $gerbang): RedirectResponse
    {
        if ($gerbang->gardu()->exists()) {
            return back()->with('error', 'Gerbang tidak dapat dihapus karena masih memiliki data gardu.');
        }

        $gerbang->delete();
        return redirect()->route('gerbang.index')->with('success', 'Gerbang berhasil dihapus.');
    }

    private function validated(Request $request, ?Gerbang $gerbang = null): array
    {
        // Koordinat: terima koma desimal (mis. "-6,9175") dan hapus spasi.
        $request->merge([
            'latitude' => $this->normalizeKoordinat($request->input('latitude')),
            'longitude' => $this->normalizeKoordinat($request->input('longitude')),
        ]);

        $ruasBaru = $request->input('ruas_id') === self::RUAS_BARU;
        $namaRuasBaru = $ruasBaru ? trim((string) $request->input('ruas_baru')) : '';

        // Jika nama ruas baru sebenarnya sudah ada (tanpa membedakan huruf besar/kecil), pakai yang lama.
        $ruasSama = $ruasBaru && $namaRuasBaru !== ''
            ? RuasTol::all()->first(fn (RuasTol $r) => mb_strtolower($r->nama_ruas) === mb_strtolower($namaRuasBaru))
            : null;

        $ruasId = $ruasBaru ? $ruasSama?->id : $request->input('ruas_id');

        $data = $request->validate([
            'ruas_id' => ['required', $ruasBaru ? Rule::in([self::RUAS_BARU]) : 'exists:ruas_tol,id'],
            'ruas_baru' => [Rule::requiredIf($ruasBaru), 'nullable', 'string', 'max:255'],
            'nama_gerbang' => [
                'required', 'string', 'max:255',
                Rule::unique('gerbang', 'nama_gerbang')
                    ->where('ruas_id', $ruasId)
                    ->ignore($gerbang?->id),
            ],
            'lokasi' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
        ], [
            'ruas_baru.required' => 'Nama ruas tol baru wajib diisi.',
            'nama_gerbang.unique' => 'Nama gerbang sudah ada pada ruas tol tersebut.',
            'latitude.required_with' => 'Latitude wajib diisi jika longitude diisi.',
            'longitude.required_with' => 'Longitude wajib diisi jika latitude diisi.',
            'latitude.between' => 'Latitude harus di antara -90 dan 90.',
            'longitude.between' => 'Longitude harus di antara -180 dan 180.',
        ]);

        if ($ruasBaru) {
            $data['ruas_id'] = ($ruasSama ?? RuasTol::create(['nama_ruas' => $namaRuasBaru]))->id;
        }
        unset($data['ruas_baru']);

        return $data;
    }

    private function normalizeKoordinat(mixed $value): ?string
    {
        $value = str_replace(',', '.', trim((string) $value));

        return $value === '' ? null : $value;
    }
}