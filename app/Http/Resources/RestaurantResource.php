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
            'nib' => $this->nib,
            'surat_halal' => $this->surat_halal,
            'latitude ' => $this->latitude ,
            'longitude' => $this->longitude,
            'is_recommended' => $this->is_recommended,
                   // Safe loading for owner
            'pemilik' => $this->whenLoaded('owner', function () {
                return new UserResource($this->owner);
            }),
            'jam_operasional' => $this->whenLoaded('jamOperasional', function () {
                return new OperationalHourResource($this->jamOperasional);
            }),
            'meja' => $this->whenLoaded('tables', function () {
                return TableResource::collection($this->tables);
            }),
            'menu' => $this->whenLoaded('menus', function () {
                return MenuResource::collection($this->menus);
            }),
            'created_at' => $this->created_at,
        ];
    }
}
