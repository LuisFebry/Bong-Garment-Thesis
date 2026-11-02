<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SumberInisiasi extends Model
{
    protected $table = 'Sumber_Inisiasi';
    protected $primaryKey = 'id_sumber';
    public $timestamps = false;

    protected $fillable = [
        'nama_sumber',
    ];

    public function pesanan(): HasMany
    {
        return $this->hasMany(Pesanan::class, 'id_sumber', 'id_sumber');
    }
}

