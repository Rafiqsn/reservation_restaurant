<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens,HasFactory, Notifiable,HasRoles;

    protected $table = 'pengguna';
    protected $fillable = ['nama', 'email', 'kata_sandi', 'peran', 'no_hp'];

    protected $hidden = ['kata_sandi'];
    public $incrementing = false;
    protected $keyType = 'string';


    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'pengguna_id');
    }

    public function restaurants()
    {
        return $this->hasMany(Restaurant::class, 'pemilik_id');
    }

    public function Notifikasi()
    {
        return $this->hasMany(Notifikasi::class, 'pengguna_id');
    }
}
