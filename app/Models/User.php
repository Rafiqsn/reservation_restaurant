<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Auth\Passwords\CanResetPassword;

class User extends Authenticatable
{
    use HasApiTokens,HasFactory, Notifiable,CanResetPassword;

    protected $table = 'pengguna';
    protected $fillable = ['id','nama', 'email', 'kata_sandi', 'peran', 'no_hp'];
    protected $hidden = ['kata_sandi'];
    public $incrementing = false;
    protected $keyType = 'string';


    public function reservasi()
    {
        return $this->hasMany(Reservation::class, 'pengguna_id');
    }

    public function restoran()
    {
        return $this->hasOne(Restaurant::class, 'pemilik_id');
    }

    public function notifikasi()
    {
        return $this->hasMany(Notifikasi::class, 'pengguna_id');
    }
}
