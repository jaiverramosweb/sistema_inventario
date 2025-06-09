<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductWarehouseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'warehouse_id'  => $this->warehouse_id,
            'unit_id'       => $this->unit_id,
            'stock'         => $this->stock,
            'umbral'        => $this->umbral,
            'warehouse'     => $this->warehouse->name,
            'unit'          => $this->unit->name,
        ];
    }
}
