<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RefoundProductResource extends JsonResource
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
            'client_id' => $this->client_id,
            'client' => $this->client->name,
            'product_id' => $this->product_id,
            'product' => [
                'title' => $this->product->title,
                'iva' => $this->product->iva,
                'imagen' => $this->product->product_imagen
            ],
            'unit_id' => $this->unit_id,
            'unit' => $this->unit->name,
            'warehouse_id' => $this->warehouse_id,
            'warehouse' => $this->warehouse->name,
            'sale_detail_id' => $this->sale_detail_id,
            'sale_id' => $this->saleDetail->sale_id,
            'quantity' => $this->quantity,
            'type' => $this->type,
            'state' => $this->state,
            'description' => $this->description,
            'resoslution_date' => $this->resoslution_date,
            'resoslution_description' => $this->resoslution_description,
            'created_at' => $this->created_at->format("Y-m-d h:i A")
        ];
    }
}
