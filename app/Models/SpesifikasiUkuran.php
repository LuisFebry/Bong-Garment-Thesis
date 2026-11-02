<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SpesifikasiUkuran extends Model
{
    protected $table = 'Spesifikasi_Ukuran';
    protected $primaryKey = 'id_spesifikasi';
    public $timestamps = false;

    protected $fillable = [
        'id_pola',
        'nama_ukuran',
        'diameter_leher',
        'panjang_pundak',
        'lingkar_lengan',
        'panjang_tangan',
        'lingkar_dada',
        'lingkar_pinggang',
        'lingkar_selangkang',
        'lingkar_paha',
        'lebar_kaki',
        'lebar_baju',
        'panjang_baju_celana',
    ];

    protected $casts = [
        'diameter_leher' => 'decimal:2',
        'panjang_pundak' => 'decimal:2',
        'lingkar_lengan' => 'decimal:2',
        'panjang_tangan' => 'decimal:2',
        'lingkar_dada' => 'decimal:2',
        'lingkar_pinggang' => 'decimal:2',
        'lingkar_selangkang' => 'decimal:2',
        'lingkar_paha' => 'decimal:2',
        'lebar_kaki' => 'decimal:2',
        'lebar_baju' => 'decimal:2',
        'panjang_baju_celana' => 'decimal:2',
    ];

    public function pola(): BelongsTo
    {
        return $this->belongsTo(Pola::class, 'id_pola', 'id_pola');
    }

    public function stokBarangJadi(): HasMany
    {
        return $this->hasMany(StokBarangJadi::class, 'id_spesifikasi', 'id_spesifikasi');
    }
}

