<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Gerbang extends Model
{
    protected $table = 'gerbang';
    protected $fillable = ['ruas_id', 'nama_gerbang', 'lokasi', 'latitude', 'longitude', 'urutan'];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function ruas(): BelongsTo
    {
        return $this->belongsTo(RuasTol::class, 'ruas_id');
    }

    public function gardu(): HasMany
    {
        return $this->hasMany(Gardu::class, 'gerbang_id');
    }

    /** Teks koordinat, mis. "-6.917464, 107.619123" (null bila belum diisi). */
    protected function koordinat(): Attribute
    {
        return Attribute::get(fn () => $this->hasKoordinat()
            ? $this->latitude.', '.$this->longitude
            : null);
    }

    /** Tautan Google Maps ke titik gerbang (null bila koordinat belum diisi). */
    protected function mapsUrl(): Attribute
    {
        return Attribute::get(fn () => $this->hasKoordinat()
            ? 'https://www.google.com/maps?q='.$this->latitude.','.$this->longitude
            : null);
    }

    public function hasKoordinat(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }
}