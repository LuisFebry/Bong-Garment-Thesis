<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $table = 'Supplier';
    protected $primaryKey = 'id_supplier';
    public $timestamps = false;

    protected $fillable = [
        'nama_supplier',
        'kontak',
        'alamat',
    ];

    public function aksesoris(): HasMany
    {
        return $this->hasMany(Aksesoris::class, 'id_supplier', 'id_supplier');
    }

    public function penerimaanBahan(): HasMany
    {
        return $this->hasMany(PenerimaanBahan::class, 'id_supplier', 'id_supplier');
    }
}

