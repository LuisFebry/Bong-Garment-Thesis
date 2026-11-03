<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Aksesoris extends Model
{
    protected $table = 'Aksesoris';
    protected $primaryKey = 'id_aksesoris';
    public $timestamps = false;

    protected $fillable = [
        'id_supplier',
        'nama_aksesoris',
        'ukuran',
        'stok',
        'satuan',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'id_supplier', 'id_supplier');
    }

    public function detailAksesorisPola(): HasMany
    {
        return $this->hasMany(DetailAksesorisPola::class, 'id_aksesoris', 'id_aksesoris');
    }

    public function pola(): BelongsToMany
    {
        return $this->belongsToMany(Pola::class, 'Detail_Aksesoris_Pola', 'id_aksesoris', 'id_pola')
                    ->using(DetailAksesorisPola::class)
                    ->withPivot('jumlah_digunakan');
    }
}

