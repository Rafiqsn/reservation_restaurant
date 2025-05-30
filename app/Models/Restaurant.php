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
        'kontak',
        'nib',
        'surat_halal',
        'is_recommended',
        'latitude',
        'longitude'
    ];
    public $incrementing = false;
    protected $keyType = 'string';

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
        return $this->hasOne(JamOperasional::class, 'restoran_id');
    }

        public function reservations()
    {
        return $this->hasMany(Reservation::class, 'restoran_id');
    }

        public function ulasan()
    {
        return $this->hasMany(Ulasan::class, 'restoran_id');
    }


}
