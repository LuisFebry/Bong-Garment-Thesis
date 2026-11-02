<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PenerimaanBahan extends Model
{
    protected $table = 'Penerimaan_Bahan';
    protected $primaryKey = 'id_penerimaan';
    public $timestamps = false;

    protected $fillable = [
        'id_supplier',
        'no_nota',
        'tgl_nota',
        'nama_kain',
        'lebar_gramasi',
        'no_kendaraan',
        'penerima',
    ];

    protected $casts = [
        'tgl_nota' => 'date',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'id_supplier', 'id_supplier');
    }

    public function detailBahanWarna(): HasMany
    {
        return $this->hasMany(DetailBahanWarna::class, 'id_penerimaan', 'id_penerimaan');
    }

    public function pesanan(): HasMany
    {
        return $this->hasMany(Pesanan::class, 'id_penerimaan', 'id_penerimaan');
    }
}

