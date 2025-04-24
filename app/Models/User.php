<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens,HasFactory, Notifiable;

    protected $table = 'pengguna';
    protected $fillable = ['id','nama', 'email', 'kata_sandi', 'peran', 'no_hp'];
    protected $hidden = ['kata_sandi'];
    public $incrementing = false;
    protected $keyType = 'string';


    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'pengguna_id');
    }

    public function restaurants()
    {
        return $this->hasOne(Restaurant::class, 'pemilik_id');
    }

    public function Notifikasi()
    {
        return $this->hasMany(Notifikasi::class, 'pengguna_id');
    }
}
