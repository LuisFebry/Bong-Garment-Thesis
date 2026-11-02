<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DetailRollBahan extends Model
{
    protected $table = 'Detail_Roll_Bahan';
    protected $primaryKey = 'id_roll';
    public $timestamps = false;

    protected $fillable = [
        'id_detail_warna',
        'no_roll_ke',
        'ukuran_awal',
        'ukuran_sisa',
        'satuan',
        'status_roll',
    ];

    protected $casts = [
        'ukuran_awal' => 'decimal:2',
        'ukuran_sisa' => 'decimal:2',
    ];

    public function detailBahanWarna(): BelongsTo
    {
        return $this->belongsTo(DetailBahanWarna::class, 'id_detail_warna', 'id_detail_warna');
    }

    public function detailGelaranKain(): HasMany
    {
        return $this->hasMany(DetailGelaranKain::class, 'id_roll', 'id_roll');
    }
}

