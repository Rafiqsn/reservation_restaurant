<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReservationMenu extends Model
{
    use HasFactory;
    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'reservasi_menu';
    protected $fillable = ['reservasi_id', 'menu_id', 'jumlah', 'subtotal'];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class, 'reservasi_id');
    }

    public function menu()
    {
        return $this->belongsTo(Menu::class, 'menu_id');
    }
}
