<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailAksesorisPola extends Model
{
    protected $table = 'Detail_Aksesoris_Pola';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = ['id_pola', 'id_aksesoris'];

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

