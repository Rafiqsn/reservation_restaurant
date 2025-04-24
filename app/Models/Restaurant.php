<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Restaurant extends Model
{
    use HasFactory;

    protected $table = 'restoran';
    protected $fillable = [
        'id',
        'pemilik_id',
        'nama',
        'lokasi',
        'deskripsi',
        'status',
        'kontak'
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'pemilik_id');
    }

    public function tables()
    {
        return $this->hasMany(Table::class, 'restoran_id');
    }

    public function menus()
    {
        return $this->hasMany(Menu::class, 'restoran_id');
    }

    public function jamOperasional()
    {
        return $this->hasMany(JamOperasional::class, 'restoran_id');
    }

}
