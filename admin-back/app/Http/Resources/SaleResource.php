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
            'client'            => [
                'id'            => $this->client->id,
                'name'          => $this->client->name,
                'type_client'   => $this->client->type_client,
                'n_document'    => $this->client->n_document,
                'type_document' => $this->client->type_document
            ],
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
            'created_at_format' => $this->created_at->format("Y-m-d"),
            'sale_details'      => $this->saleDetails->map(function ($detail) {
                return [
                    'id'                => $detail->id,
                    'product_id'        => $detail->product_id,
                    'product'           => [
                        'id'            => $detail->product->id,
                        'title'         => $detail->product->title,
                        'sku'           => $detail->product->sku,
                        'tax_selected'  => $detail->product->tax_selected,
                        'importe_iva'   => $detail->product->importe_iva,
                        'warehouses'    => $detail->product->warehouses->map(function ($warehouse){
                            return [
                                'id'            => $warehouse->id,
                                'warehouse_id'  => $warehouse->warehouse_id,
                                'unit_id'       => $warehouse->unit_id,
                                'stock'         => $warehouse->stock,
                                'umbral'        => $warehouse->umbral,
                                'warehouse'     => $warehouse->warehouse->name,
                                'unit'          => $warehouse->unit->name,
                            ];
                        }),
                        "wallets"       => $detail->product->wallets->map(function ($wallet){
                            return [
                                'id'                => $wallet->id,
                                'type_client'       => $wallet->type_client,
                                'type_client_name'  => $wallet->type_client == 1 ? 'Cliente final' : 'Cliente empresa',
                                'unit_id'           => $wallet->unit_id,
                                'sucursal_id'       => $wallet->sucursal_id,
                                'price'             => $wallet->price,
                                'sucursal'          => $wallet->sucursal ? $wallet->sucursal->name : null,
                                'unit'              => $wallet->unit->name,
                            ];
                        }),
                        'price_general' => $detail->product->price_general,
                        'price_company' => $detail->product->price_company,
                        'is_discount'   => $detail->product->is_discount,
                        'max_descount'  => $detail->product->max_descount,
                        'available'     => $detail->product->available,
                        'is_gift'       => $detail->product->is_gift,
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
                ];  
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
