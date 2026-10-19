<?php

namespace App\Support;

use App\Models\RuasTol;

class CascadeData
{
    public static function tree(): array
    {
        return RuasTol::with([
                'gerbang' => fn ($q) => $q->orderBy('nama_gerbang'),
                'gerbang.gardu',
            ])
            ->orderBy('nama_ruas')
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'nama' => $r->nama_ruas,
                'gerbang' => $r->gerbang->map(fn ($g) => [
                    'id' => $g->id,
                    'nama' => $g->nama_gerbang,
                    'gardu' => $g->gardu
                        ->sortBy(fn ($x) => sprintf('%05d-%s', (int) $x->nomor_gardu, $x->nomor_gardu))
                        ->values()
                        ->map(fn ($x) => ['id' => $x->id, 'nomor' => $x->nomor_gardu])
                        ->all(),
                ])->values()->all(),
            ])
            ->values()
            ->all();
    }
}