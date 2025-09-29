<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransportResource extends JsonResource
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
            'warehause_start_id' => $this->warehause_start_id,
            'warehouse_start' => $this->warehouseStart->name,
            'warehause_end_id' => $this->warehause_end_id,
            'warehouse_end' => $this->warehouseEnd->name,
            'user_id' => $this->user_id,
            'user' => $this->user->name,
            'date_emision' => Carbon::parse( $this->date_emision)->format("Y-m-d"),
            'state' => $this->state,
            'total' => $this->total,
            'impote' => $this->impote,
            'iva' => $this->iva,
            'date_delivery' => $this->date_delivery,
            'description' => $this->description,
            'created_at' => $this->created_at->format("Y-m-d h:i A"),
            'details' => $this->transportDetails->map(function ($detail) {
                return new TransportDetailResource($detail);
            }),
        ];
    }
}
