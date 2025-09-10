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
            'sale_details'      => $this->saleDetails->map(function ($detail) {
                return [
                    'id'                => $detail->id,
                    'product_id'        => $detail->product_id,
                    'product'           => [
                        'id'            => $detail->product->id,
                        'title'         => $detail->product->title,
                        'sku'           => $detail->product->sku,
                    ],
                    'unit_id'           => $detail->unit_id,
                    'unit'              => $detail->unit->name,
                    'warehouse_id'      => $detail->warehouse_id,
                    'warehouse'         => $detail->warehouse->name,
                    'product_categoryid' => $detail->product_categoryid,
                    'product_category'  => $detail->productCategory->title,
                    'quantity'          => $detail->quantity,
                    'price_unit'        => $detail->price_unit,
                    'discount'          => $detail->discount,
                    'iva'               => $detail->iva,
                    'subtotal'          => $detail->subtotal,
                    'total'             => $detail->total,
                    'state_attention'   => $detail->state_attention,
                    'description'       => $detail->description,
                    'quantity_pending'  => $detail->quantity_pending,
                    'state_attention'   => $detail->state_attention,
                ];  
            }),
            'payments'          => $this->payments->map(function ($payment) {
                return [
                    'id'                => $payment->id,
                    'payment_method'    => $payment->payment_method,
                    'n_trasaction'      => $payment->n_trasaction,
                    'banco'             => $payment->banco,
                    'amount'            => $payment->amount
                ];
            })
        ];
    }
}
