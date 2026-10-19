<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipeGardu extends Model
{
    protected $table = 'tipe_gardu';
    protected $fillable = ['nama_tipe'];

    public function gardu(): HasMany
    {
        return $this->hasMany(Gardu::class, 'tipe_gardu_id');
    }
}