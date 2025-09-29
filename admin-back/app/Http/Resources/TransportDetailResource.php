<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransportDetailResource extends JsonResource
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
                "warehouses" => $this->product->warehouses->map(function ($warehouse) {
                    return [
                        'id'            => $warehouse->id,
                        'warehouse_id'  => $warehouse->warehouse_id,
                        'unit_id'       => $warehouse->unit_id,
                        'stock'         => $warehouse->stock,
                        'umbral'        => $warehouse->umbral,
                        'warehouse'     => $warehouse->warehouse->name,
                        'unit'          => $warehouse->unit->name,
                    ];
                })
            ],
            'unit_id' => $this->unit_id,
            'unit' => $this->unit->name,
            'quantity' => $this->quantity,
            'price_unit' => $this->price_unit,
            'total' => $this->total,
            'state' => $this->state,
            'user_delivery' => $this->user_delivery_id ? [
                'id' => $this->userIn->id,
                'name' => $this->userIn->name,
            ] : null,
            'date_delivery' => $this->date_delivery ? Carbon::parse( $this->date_delivery)->format("Y-m-d") : null,
            'user_exit' => $this->user_exit_id ? [
                'id' => $this->userOut->id,
                'name' => $this->userOut->name,
            ] : null,
            'date_exit' => $this->date_exit ? Carbon::parse( $this->date_exit)->format("Y-m-d") : null,
            'description' => $this->description,
            'created_at' => $this->created_at->format("Y-m-d h:i A")
        ];
    }
}
