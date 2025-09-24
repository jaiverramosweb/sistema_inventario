<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PuchaseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return[
            'id' => $this->id,
            'warehouse_id' => $this->warehouse_id,
            'warehouse' => $this->warehouse->name,
            'user_id' => $this->user_id,
            'user' => $this->user->name,
            'sucuarsal_id' => $this->sucuarsal_id,
            'sucursale' => $this->sucursal->name,
            'date_emition' => Carbon::parse( $this->date_emition)->format("Y-m-d"),
            'state' => $this->state,
            'type_comprobant' => $this->type_comprobant,
            'n_comprobant' => $this->n_comprobant,
            'provider_id' => $this->provider_id,
            'provider' => $this->provider->name,
            'total' => $this->total,
            'immporte' => $this->immporte,
            'iva' => $this->iva,
            'date_delivery' => $this->date_delivery,
            'description' => $this->description,
            'created_at' => $this->created_at->format("Y-m-d h:i A"),
            'details' => $this->puchaseDetails->map(function ($detail) {
                return [
                    'id' => $detail->id,
                    'product_id' => $detail->product_id,
                    'product' => [
                        'title' => $detail->product->title,
                        'sku' => $detail->product->sku,
                    ],
                    'unit_id' => $detail->unit_id,
                    'unit' => $detail->unit->name,
                    'quantity' => $detail->quantity,
                    'price_unit' => $detail->price_unit,
                    'total' => $detail->total,
                    'state' => $detail->state,
                    'user_delivery' => $detail->user_delivery,
                    'date_delivery' => $detail->date_delivery,
                    'description' => $detail->description,
                    'created_at' => $detail->created_at->format("Y-m-d h:i A")
                ];
            }),
        ];
    }
}
