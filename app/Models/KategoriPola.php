<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KategoriPola extends Model
{
    protected $table = 'Kategori_Pola';
    protected $primaryKey = 'id_kategori';
    public $timestamps = false;

    protected $fillable = [
        'nama_kategori',
    ];

    public function pola(): HasMany
    {
        return $this->hasMany(Pola::class, 'id_kategori', 'id_kategori');
    }
}

