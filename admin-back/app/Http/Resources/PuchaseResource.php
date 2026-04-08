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
        $dateEmission = $this->date_emition ? Carbon::parse($this->date_emition)->format('Y-m-d') : null;

        return[
            'id' => $this->id,
            'warehouse_id' => $this->warehouse_id,
            'warehouse' => optional($this->warehouse)->name,
            'user_id' => $this->user_id,
            'user' => optional($this->user)->name,
            'sucuarsal_id' => $this->sucuarsal_id,
            'sucursal_id' => $this->sucuarsal_id,
            'sucursale' => optional($this->sucursal)->name,
            'sucursal' => optional($this->sucursal)->name,
            'date_emition' => $dateEmission,
            'date_emission' => $dateEmission,
            'state' => $this->state,
            'type_comprobant' => $this->type_comprobant,
            'n_comprobant' => $this->n_comprobant,
            'provider_id' => $this->provider_id,
            'provider' => optional($this->provider)->name,
            'total' => $this->total,
            'immporte' => $this->immporte,
            'importe' => $this->immporte,
            'iva' => $this->iva,
            'date_delivery' => $this->date_delivery,
            'description' => $this->description,
            'created_at' => $this->created_at?->format("Y-m-d h:i A"),
            'details' => $this->puchaseDetails->map(function ($detail) {
                return new PurchaseDetailResource($detail);
            }),
            'puchase_details' => $this->puchaseDetails->map(function ($detail) {
                return new PurchaseDetailResource($detail);
            }),
            'purchase_details' => $this->puchaseDetails->map(function ($detail) {
                return new PurchaseDetailResource($detail);
            }),
        ];
    }
}
