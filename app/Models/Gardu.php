<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Gardu extends Model
{
    public const STATUS = ['Aktif', 'Tidak Aktif', 'Pemeliharaan'];
    public const ARAH = ['Entrance', 'Exit'];

    protected $table = 'gardu';
    protected $fillable = ['gerbang_id', 'nomor_gardu', 'tipe_gardu_id', 'arah', 'status', 'catatan'];

    public function gerbang(): BelongsTo
    {
        return $this->belongsTo(Gerbang::class, 'gerbang_id');
    }

    public function tipe(): BelongsTo
    {
        return $this->belongsTo(TipeGardu::class, 'tipe_gardu_id');
    }

    public function perangkat(): HasOne
    {
        return $this->hasOne(PerangkatGardu::class, 'gardu_id');
    }
}