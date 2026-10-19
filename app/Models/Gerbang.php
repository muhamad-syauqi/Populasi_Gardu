<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Gerbang extends Model
{
    protected $table = 'gerbang';
    protected $fillable = ['ruas_id', 'nama_gerbang', 'lokasi'];

    public function ruas(): BelongsTo
    {
        return $this->belongsTo(RuasTol::class, 'ruas_id');
    }

    public function gardu(): HasMany
    {
        return $this->hasMany(Gardu::class, 'gerbang_id');
    }
}