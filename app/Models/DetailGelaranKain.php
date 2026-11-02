<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailGelaranKain extends Model
{
    protected $table = 'Detail_Gelaran_Kain';
    protected $primaryKey = 'id_gelar';
    public $timestamps = false;

    protected $fillable = [
        'id_potong_warna',
        'id_roll',
        'panjang_gelar',
        'jumlah_tumpuk',
        'total_lembar_gelar',
    ];

    protected $casts = [
        'panjang_gelar' => 'decimal:2',
        'jumlah_tumpuk' => 'integer',
        'total_lembar_gelar' => 'integer',
    ];

    public function detailPemotonganWarna(): BelongsTo
    {
        return $this->belongsTo(DetailPemotonganWarna::class, 'id_potong_warna', 'id_potong_warna');
    }

    public function detailRollBahan(): BelongsTo
    {
        return $this->belongsTo(DetailRollBahan::class, 'id_roll', 'id_roll');
    }
}

