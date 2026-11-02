<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'User';
    protected $primaryKey = 'id_user';
    public $timestamps = false;

    protected $fillable = [
        'username',
        'password',
        'nama_user',
        'role',
    ];

    protected $hidden = [
        'password',
    ];

    public function pesananAdmin(): HasMany
    {
        return $this->hasMany(Pesanan::class, 'id_user_admin', 'id_user');
    }

    public function pemotonganSeri(): HasMany
    {
        return $this->hasMany(PemotonganSeri::class, 'id_user_pemotong', 'id_user');
    }

    public function pemeriksaanQc(): HasMany
    {
        return $this->hasMany(Qc::class, 'id_user_qc', 'id_user');
    }
}

