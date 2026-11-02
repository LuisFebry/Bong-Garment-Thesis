<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DetailPemotonganWarna extends Model
{
    protected $table = 'Detail_Pemotongan_Warna';
    protected $primaryKey = 'id_potong_warna';
    public $timestamps = false;

    protected $fillable = [
        'id_potong',
        'id_detail_warna',
        'total_lembar_direncanakan',
        'kain_terpakai',
    ];

    protected $casts = [
        'total_lembar_direncanakan' => 'integer',
        'kain_terpakai' => 'decimal:2',
    ];

    public function pemotonganSeri(): BelongsTo
    {
        return $this->belongsTo(PemotonganSeri::class, 'id_potong', 'id_potong');
    }

    public function detailBahanWarna(): BelongsTo
    {
        return $this->belongsTo(DetailBahanWarna::class, 'id_detail_warna', 'id_detail_warna');
    }

    public function detailGelaranKain(): HasMany
    {
        return $this->hasMany(DetailGelaranKain::class, 'id_potong_warna', 'id_potong_warna');
    }
}

