<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RestaurantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama' => $this->nama,
            'lokasi' => $this->lokasi,
            'deskripsi' => $this->deskripsi,
            'status' => $this->status,
            'kontak' => $this->kontak,
            'pemilik_id' => new UserResource($this->whenLoaded('owner')),
            'jam_operasional' => OperationalHourResource::collection($this->whenLoaded('jamOperasional')),
            'meja' => TableResource::collection($this->whenLoaded('tables')),
            'menu' => MenuResource::collection($this->whenLoaded('menus')),
            'created_at' => $this->created_at,
        ];
    }
}
