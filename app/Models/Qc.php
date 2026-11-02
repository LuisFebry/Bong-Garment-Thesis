<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Qc extends Model
{
    protected $table = 'QC';
    protected $primaryKey = 'id_qc';
    public $timestamps = false;

    protected $fillable = [
        'id_pesanan',
        'id_user_qc',
        'tahap_qc',
        'tgl_pemeriksaan',
        'jumlah_diperiksa',
        'jumlah_lolos',
        'jumlah_cacat',
        'jenis_cacat',
        'catatan_qc',
        'status_qc',
        'tgl_target_perbaikan',
        'tgl_selesai_perbaikan',
    ];

    protected $casts = [
        'tgl_pemeriksaan' => 'datetime',
        'jumlah_diperiksa' => 'integer',
        'jumlah_lolos' => 'integer',
        'jumlah_cacat' => 'integer',
        'tgl_target_perbaikan' => 'date',
        'tgl_selesai_perbaikan' => 'datetime',
    ];

    public function pesanan(): BelongsTo
    {
        return $this->belongsTo(Pesanan::class, 'id_pesanan', 'id_pesanan');
    }

    public function userQc(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user_qc', 'id_user');
    }
}

