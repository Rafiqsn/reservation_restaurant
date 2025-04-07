<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $table = 'nota_pesanan';
    protected $fillable = ['reservasi_id', 'total_harga', 'status'];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class, 'reservasi_id');
    }
}
