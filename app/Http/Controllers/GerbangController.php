<?php

namespace App\Http\Controllers;

use App\Models\Gerbang;
use App\Models\RuasTol;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GerbangController extends Controller
{
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
        Gerbang::create($this->validated($request));
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
        $gerbang->update($this->validated($request, $gerbang));
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
        return $request->validate([
            'ruas_id' => ['required', 'exists:ruas_tol,id'],
            'nama_gerbang' => [
                'required', 'string', 'max:255',
                Rule::unique('gerbang', 'nama_gerbang')
                    ->where('ruas_id', $request->input('ruas_id'))
                    ->ignore($gerbang?->id),
            ],
            'lokasi' => ['nullable', 'string', 'max:255'],
        ], [
            'nama_gerbang.unique' => 'Nama gerbang sudah ada pada ruas tol tersebut.',
        ]);
    }
}