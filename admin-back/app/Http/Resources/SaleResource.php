<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
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
            'user_id'           => $this->user_id,
            'user'              => $this->user->name,
            'client_id'         => $this->client_id,
            'client'            => $this->client->name,
            'type_client'       => $this->type_client,
            'sucursal_id'       => $this->sucursal_id,
            'sucursale'         => $this->sucursale->name,
            'subtotal'          => $this->subtotal,
            'discount'          => $this->discount,
            'total'             => $this->total,
            'iva'               => $this->iva,
            'state'             => $this->state,
            'state_mayment'     => $this->state_mayment,
            'debt'              => $this->debt,
            'paid_out'          => $this->paid_out,
            'date_validation'   => $this->date_validation,
            'date_completed'    => $this->date_completed,
            'description'       => $this->description,
            'state_delivery'    => $this->state_delivery,
            'created_at'        => $this->created_at->format("Y-m-d h:i A"),
        ];
    }
}
