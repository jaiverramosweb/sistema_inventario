<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseDetailResource extends JsonResource
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
            'product_id' => $this->product_id,
            'product' => [
                'title' => $this->product->title,
                'sku' => $this->product->sku,
            ],
            'unit_id' => $this->unit_id,
            'unit' => $this->unit->name,
            'quantity' => $this->quantity,
            'price_unit' => $this->price_unit,
            'total' => $this->total,
            'state' => $this->state,
            'user_delivery' => $this->user_delivery ? [
                'id' => $this->userDelivery->id,
                'name' => $this->userDelivery->name,
            ] : null,
            'date_delivery' => $this->date_delivery ? Carbon::parse( $this->date_delivery)->format("Y-m-d") : null,
            'description' => $this->description,
            'created_at' => $this->created_at->format("Y-m-d h:i A")
        ];
    }
}
