<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleDetailsResource extends JsonResource
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
        'product_id'        => $this->product_id,
        'product'           => [
            'id'            => $this->product->id,
            'title'         => $this->product->title,
            'sku'           => $this->product->sku,
            'tax_selected'  => $this->product->tax_selected,
            'importe_iva'   => $this->product->importe_iva,
            'warehouses'    => $this->product->warehouses->map(function ($warehouse){
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
            "wallets"       => $this->product->wallets->map(function ($wallet){
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
            'price_general' => $this->product->price_general,
            'price_company' => $this->product->price_company,
            'is_discount'   => $this->product->is_discount,
            'max_descount'  => $this->product->max_descount,
            'available'     => $this->product->available,
            'is_gift'       => $this->product->is_gift,
        ],
        'unit_id'           => $this->unit_id,
        'unit'              => $this->unit->name,
        'warehouse_id'      => $this->warehouse_id,
        'warehouse'         => $this->warehouse->name,
        'product_categoryid' => $this->product_categoryid,
        'product_category'  => $this->productCategory->title,
        'quantity'          => $this->quantity,
        'price_unit'        => $this->price_unit,
        'discount'          => $this->discount,
        'iva'               => $this->iva,
        'subtotal'          => $this->subtotal,
        'total'             => $this->total,
        'state_attention'   => $this->state_attention,
        'description'       => $this->description,
        'quantity_pending'  => $this->quantity_pending
        ];
    }
}
