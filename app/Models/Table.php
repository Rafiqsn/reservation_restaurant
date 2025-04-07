<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Table extends Model
{
    use HasFactory;

    protected $table = 'kursi';
    protected $fillable = ['restoran_id', 'nomor_kursi', 'kapasitas', 'posisi', 'status'];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class, 'restoran_id');
    }
}
