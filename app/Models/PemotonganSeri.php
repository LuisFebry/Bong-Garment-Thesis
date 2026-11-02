<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PemotonganSeri extends Model
{
    protected $table = 'Pemotongan_Seri';
    protected $primaryKey = 'id_potong';
    public $timestamps = false;

    protected $fillable = [
        'id_pesanan',
        'id_user_pemotong',
        'kode_seri',
        'jenis_variasi',
        'total_lembar_hasil',
        'tgl_potong',
    ];

    protected $casts = [
        'tgl_potong' => 'datetime',
        'total_lembar_hasil' => 'integer',
    ];

    public function pesanan(): BelongsTo
    {
        return $this->belongsTo(Pesanan::class, 'id_pesanan', 'id_pesanan');
    }

    public function userPemotong(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user_pemotong', 'id_user');
    }

    public function detailPemotonganWarna(): HasMany
    {
        return $this->hasMany(DetailPemotonganWarna::class, 'id_potong', 'id_potong');
    }
}

