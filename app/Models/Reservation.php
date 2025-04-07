<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    protected $table = 'reservasi';
    protected $fillable = ['pengguna_id', 'restoran_id', 'kursi_id', 'tanggal', 'waktu', 'status'];

    public function user()
    {
        return $this->belongsTo(User::class, 'pengguna_id');
    }

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class, 'restoran_id');
    }

    public function table()
    {
        return $this->belongsTo(Table::class, 'kursi_id');
    }

    public function reservationMenus()
    {
        return $this->hasMany(ReservationMenu::class, 'reservasi_id');
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class, 'reservasi_id');
    }
}
