<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ulasan extends Model
{
    use HasFactory;
    public $incrementing = false;
    protected $keyType = 'string';

    protected $table = 'ulasan';
    protected $fillable = ['id', 'reservasi_id', 'restoran_id', 'pengguna_id', 'rating', 'komentar'];

    public function reservasi()
    {
        return $this->belongsTo(Reservation::class, 'reservasi_id');
    }
    public function pengguna()
    {
        return $this->belongsTo(User::class, 'pengguna_id');
    }

    public function restoran()
    {
        return $this->belongsTo(Restaurant::class, 'restoran_id');
    }

}
