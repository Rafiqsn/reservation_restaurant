<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Notifikasi extends Model
{
    use HasUuids;

    protected $table = 'notifikasi';

    protected $fillable = [
        'pengguna_id',
        'reservasi_id',
        'pesan',
        'status',
    ];

    public function User()
    {
        return $this->belongsTo(User::class);
    }

    public function reservasi()
    {
        return $this->belongsTo(Reservation::class);
    }
}
