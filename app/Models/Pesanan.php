<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pesanan extends Model
{
    protected $table = 'Pesanan';
    protected $primaryKey = 'id_pesanan';
    public $timestamps = false;

    protected $fillable = [
        'id_pola',
        'id_sumber',
        'id_penerimaan',
        'id_user_admin',
        'jenis_pesanan',
        'nama_mitra',
        'tgl_order',
        'deadline_awal',
        'deadline_terbaru',
        'tgl_perubahan_deadline',
        'alasan_perubahan_deadline',
        'status_pesanan',
    ];

    protected $casts = [
        'tgl_order' => 'date',
        'deadline_awal' => 'date',
        'deadline_terbaru' => 'date',
        'tgl_perubahan_deadline' => 'datetime',
    ];

    public function pola(): BelongsTo
    {
        return $this->belongsTo(Pola::class, 'id_pola', 'id_pola');
    }

    public function sumberInisiasi(): BelongsTo
    {
        return $this->belongsTo(SumberInisiasi::class, 'id_sumber', 'id_sumber');
    }

    public function penerimaanBahan(): BelongsTo
    {
        return $this->belongsTo(PenerimaanBahan::class, 'id_penerimaan', 'id_penerimaan');
    }

    public function userAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user_admin', 'id_user');
    }

    public function pemotonganSeri(): HasMany
    {
        return $this->hasMany(PemotonganSeri::class, 'id_pesanan', 'id_pesanan');
    }

    public function qc(): HasMany
    {
        return $this->hasMany(Qc::class, 'id_pesanan', 'id_pesanan');
    }

    /**
     * Effective deadline for EDF (Earliest Deadline First) scheduling
     */
    public function getEffectiveDeadlineAttribute()
    {
        return $this->deadline_terbaru ?? $this->deadline_awal;
    }
}

