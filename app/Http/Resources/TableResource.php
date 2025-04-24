<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;


class TableResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'nomor_kursi' => $this->nomor_kursi,
            'kapasitas' => $this->kapasitas,
            'posisi' => $this->posisi,
            'status' => $this->status,
        ];
    }
}
