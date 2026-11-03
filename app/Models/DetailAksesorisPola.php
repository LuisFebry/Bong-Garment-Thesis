<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailAksesorisPola extends Pivot
{
    protected $table = 'Detail_Aksesoris_Pola';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'id_pola',
        'id_aksesoris',
        'jumlah_digunakan',
    ];

    public function pola(): BelongsTo
    {
        return $this->belongsTo(Pola::class, 'id_pola', 'id_pola');
    }

    public function aksesoris(): BelongsTo
    {
        return $this->belongsTo(Aksesoris::class, 'id_aksesoris', 'id_aksesoris');
    }
}
