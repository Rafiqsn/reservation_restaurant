<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class JamOperasional extends Model
{
    use HasUuids;
    public $incrementing = false;
    protected $keyType = 'string';

    protected $table = 'jam_operasional';

    protected $fillable = [
        'restoran_id',
        'hari',
        'jam_buka',
        'jam_tutup'
    ];

    public function Restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }
}
