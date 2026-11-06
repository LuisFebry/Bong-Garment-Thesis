<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiwayatStokBarangJadi extends Model
{
    protected $table = 'Riwayat_Stok_Barang_Jadi';
    protected $primaryKey = 'id_riwayat_stok';
    public $timestamps = false;

    protected $fillable = [
        'id_stok',
        'id_pola',
        'id_spesifikasi',
        'nama_warna',
        'jenis_transaksi',
        'jumlah_pcs',
        'stok_sebelum',
        'stok_sesudah',
        'kategori_alasan',
        'nama_mitra',
        'keterangan',
        'id_user',
        'tgl_transaksi',
    ];

    protected $casts = [
        'tgl_transaksi' => 'datetime',
        'jumlah_pcs' => 'integer',
        'stok_sebelum' => 'integer',
        'stok_sesudah' => 'integer',
    ];

    public function stokBarangJadi(): BelongsTo
    {
        return $this->belongsTo(StokBarangJadi::class, 'id_stok', 'id_stok');
    }

    public function pola(): BelongsTo
    {
        return $this->belongsTo(Pola::class, 'id_pola', 'id_pola');
    }

    public function spesifikasiUkuran(): BelongsTo
    {
        return $this->belongsTo(SpesifikasiUkuran::class, 'id_spesifikasi', 'id_spesifikasi');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }
}

