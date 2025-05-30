<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Reservation extends Model
{
    use HasFactory;
    public $incrementing = false;
    protected $keyType = 'string';

    protected $table = 'reservasi';
    protected $fillable = ['id','pengguna_id', 'restoran_id', 'kursi_id', 'tanggal', 'waktu','jumlah_orang','catatan', 'status','nomor_reservasi','total_harga'];

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

        public function menu()
    {
        return $this->hasMany(ReservationMenu::class, 'reservasi_id');
    }
        public function kursi()
    {
        return $this->belongsTo(Table::class);
    }

        public function ulasan()
    {
        return $this->hasOne(Ulasan::class, 'reservasi_id');
    }

}
