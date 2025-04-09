<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama' => $this->nama,
            'email' => $this->email,
            'peran' => $this->peran,
            'no_hp' => $this->no_hp,
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
