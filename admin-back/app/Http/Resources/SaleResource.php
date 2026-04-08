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
            'user'              => optional($this->user)->name,
            'client_id'         => $this->client_id,
            'client'            => [
                'id'            => optional($this->client)->id,
                'name'          => optional($this->client)->name,
                'type_client'   => optional($this->client)->type_client,
                'n_document'    => optional($this->client)->n_document,
                'type_document' => optional($this->client)->type_document
            ],
            'type_client'       => $this->type_client,
            'sucursal_id'       => $this->sucursal_id,
            'sucursale'         => optional($this->sucursale)->name,
            'subtotal'          => $this->subtotal,
            'discount'          => $this->discount,
            'total'             => $this->total,
            'iva'               => $this->iva,
            'state'             => $this->state,
            'state_mayment'     => $this->state_mayment,
            'state_payment'     => $this->state_mayment,
            'debt'              => $this->debt,
            'paid_out'          => $this->paid_out,
            'date_validation'   => $this->date_validation,
            'date_completed'    => $this->date_completed,
            'description'       => $this->description,
            'state_delivery'    => $this->state_delivery,
            'created_at'        => $this->created_at?->format("Y-m-d h:i A"),
            'created_at_format' => $this->created_at?->format("Y-m-d"),
            'sale_details'      => $this->saleDetails->map(function ($detail) {
                return new SaleDetailsResource($detail);  
            }),
            'payments'          => $this->payments->map(function ($payment) {
                return [
                    'id'                => $payment->id,
                    'method_payment'    => $payment->payment_method,
                    'n_trasaction'      => $payment->n_trasaction,
                    'banco'             => $payment->banco,
                    'amount'            => $payment->amount
                ];
            })
        ];
    }
}
