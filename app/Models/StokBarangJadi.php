<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StokBarangJadi extends Model
{
    protected $table = 'Stok_Barang_Jadi';
    protected $primaryKey = 'id_stok';
    public $timestamps = false;

    protected $fillable = [
        'id_pola',
        'id_spesifikasi',
        'nama_warna',
        'jumlah_stok_pcs',
        'tgl_update_terakhir',
    ];

    protected $casts = [
        'tgl_update_terakhir' => 'datetime',
        'jumlah_stok_pcs' => 'integer',
    ];

    public function pola(): BelongsTo
    {
        return $this->belongsTo(Pola::class, 'id_pola', 'id_pola');
    }

    public function spesifikasiUkuran(): BelongsTo
    {
        return $this->belongsTo(SpesifikasiUkuran::class, 'id_spesifikasi', 'id_spesifikasi');
    }
}

