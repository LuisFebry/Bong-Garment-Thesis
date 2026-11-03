<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Pola extends Model
{
    protected $table = 'Pola';
    protected $primaryKey = 'id_pola';
    public $timestamps = false;

    protected $fillable = [
        'id_kategori',
        'kode_pola',
        'nama_pola',
    ];

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriPola::class, 'id_kategori', 'id_kategori');
    }

    public function spesifikasiUkuran(): HasMany
    {
        return $this->hasMany(SpesifikasiUkuran::class, 'id_pola', 'id_pola');
    }

    public function detailAksesorisPola(): HasMany
    {
        return $this->hasMany(DetailAksesorisPola::class, 'id_pola', 'id_pola');
    }

    public function aksesoris(): BelongsToMany
    {
        return $this->belongsToMany(Aksesoris::class, 'Detail_Aksesoris_Pola', 'id_pola', 'id_aksesoris')
                    ->using(DetailAksesorisPola::class)
                    ->withPivot('jumlah_digunakan');
    }

    public function pesanan(): HasMany
    {
        return $this->hasMany(Pesanan::class, 'id_pola', 'id_pola');
    }

    public function stokBarangJadi(): HasMany
    {
        return $this->hasMany(StokBarangJadi::class, 'id_pola', 'id_pola');
    }
}

