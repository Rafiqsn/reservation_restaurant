<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

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
        'foto',
        'status',
        'denah_meja',
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

       public function getFotoUrlAttribute()
    {
        return $this->foto ? URL::to('/foto/' . $this->foto) : null;
    }

    public function getSuratHalalUrlAttribute()
    {
        return $this->surat_halal ? URL::to('/surat_halal/' . $this->surat_halal) : null;
    }

    public function getDenahMejaUrlAttribute()
    {
        return url("storage/denah/{$this->id}/{$this->denah_meja}");
    }


        public function fotoTambahan()
    {
        return $this->hasMany(RestoranFoto::class, 'restoran_id');
    }


}
