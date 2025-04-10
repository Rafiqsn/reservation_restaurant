<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use HasFactory;

    protected $table = 'menu';
    protected $fillable = ['restoran_id', 'nama', 'deskripsi', 'harga', 'status'];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class, 'restoran_id');
    }

    public function reservationMenus()
    {
        return $this->hasMany(ReservationMenu::class, 'menu_id');
    }
}
