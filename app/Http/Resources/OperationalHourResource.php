<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;


class OperationalHourResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'jam_buka' => $this->jam_buka,
            'jam_tutup' => $this->jam_tutup,
        ];
    }
}
