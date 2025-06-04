<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RestoranFoto extends Model
{
    use HasFactory;
    protected $table = 'restoran_foto';
    protected $fillable = ['id', 'restoran_id', 'nama_file',];
    public $incrementing = false;
    protected $keyType = 'string';

    public function restoran()
    {
        return $this->belongsTo(Restaurant::class, 'restoran_id');
    }

    protected $appends = ['url'];

    public function getUrlAttribute()
    {
        return url('foto/' . $this->nama_file);
    }
}
