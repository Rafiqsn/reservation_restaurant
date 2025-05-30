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
            'foto' => $this->foto,
            'nib' => $this->nib,
            'surat_halal' => $this->surat_halal,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'is_recommended' => $this->is_recommended,

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

            'ulasan' => $this->whenLoaded('ulasan', function () {
                return $this->ulasan->map(function ($ulasan) {
                    return [
                        'id' => $ulasan->id,
                        'rating' => $ulasan->rating,
                        'komentar' => $ulasan->komentar,
                        'pengulas' => optional($ulasan->reservasi->user)->nama,
                    ];
                });
            }),

            'rata_rata_rating' => $this->whenLoaded('ulasan', function () {
                return round($this->ulasan->avg('rating'), 1);
            }),

            'created_at' => $this->created_at,
        ];
    }

}
