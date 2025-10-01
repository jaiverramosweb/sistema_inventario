<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversionResource extends JsonResource
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
            'user_id' => $this->user_id,
            'user' => $this->user->name,
            'product_id' => $this->product_id,
            'product' => [
                'title' => $this->product->title,
                'sku' => $this->product->sku,
                'imagen' => $this->product->product_imagen
            ],
            'warehause_id' => $this->warehause_id,
            'warehause' => $this->warehause->name,
            'unit_start_id' => $this->unit_start_id,
            'unit_start' => $this->unitStart->name,
            'unit_end_id' => $this->unit_end_id,
            'unit_end' => $this->unitEnd->name,
            'quantity_start' => $this->quantity_start,
            'quantity_end' => $this->quantity_end,
            'description' => $this->description,
            'created_at' => $this->created_at->format("Y-m-d h:i A")
        ];
    }
}
