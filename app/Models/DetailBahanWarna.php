<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DetailBahanWarna extends Model
{
    protected $table = 'Detail_Bahan_Warna';
    protected $primaryKey = 'id_detail_warna';
    public $timestamps = false;

    protected $fillable = [
        'id_penerimaan',
        'nama_warna',
        'kode_warna',
        'total_roll',
        'total_ukuran',
        'satuan_ukur',
    ];

    protected $casts = [
        'total_ukuran' => 'decimal:2',
    ];

    public function penerimaanBahan(): BelongsTo
    {
        return $this->belongsTo(PenerimaanBahan::class, 'id_penerimaan', 'id_penerimaan');
    }

    public function detailRollBahan(): HasMany
    {
        return $this->hasMany(DetailRollBahan::class, 'id_detail_warna', 'id_detail_warna');
    }

    public function detailPemotonganWarna(): HasMany
    {
        return $this->hasMany(DetailPemotonganWarna::class, 'id_detail_warna', 'id_detail_warna');
    }
}

