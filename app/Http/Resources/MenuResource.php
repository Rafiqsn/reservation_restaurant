<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;


class MenuResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama' => $this->nama,
            'deskripsi' => $this->deskripsi,
            'jenis' => $this->jenis,
            'harga' => $this->harga,
            'foto' => $this->foto,
            'status' => $this->status,
            'highlight' => $this->highlight,
        ];
    }
}
