<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductPriceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'type_client'       => $this->type_client,
            'type_client_name'  => $this->type_client == 1 ? 'Cliente final' : 'Cliente empresa',
            'unit_id'           => $this->unit_id,
            'sucursal_id'       => $this->sucursal_id,
            'price'             => $this->price,
            'sucursal'          => $this->sucursal ? $this->sucursal->name : null,
            'unit'              => $this->unit->name,
        ];
    }
}
