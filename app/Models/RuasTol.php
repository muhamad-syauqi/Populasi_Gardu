<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RuasTol extends Model
{
    protected $table = 'ruas_tol';
    protected $fillable = ['nama_ruas'];

    public function gerbang(): HasMany
    {
        return $this->hasMany(Gerbang::class, 'ruas_id');
    }
}