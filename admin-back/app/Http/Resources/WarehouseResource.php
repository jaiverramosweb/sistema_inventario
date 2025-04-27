<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'address' => $this->address,
            'sucursal_id' => $this->sucursal_id,
            'sucursal' => $this->sucursal?->name,
            'status' => $this->status,
            'created_at' => $this->created_at->format("Y-m-d"),
        ];
    }
}
