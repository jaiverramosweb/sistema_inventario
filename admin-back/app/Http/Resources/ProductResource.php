<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'title'         => $this->title,
            'category_id'   => $this->category_id,
            'price_general' => $this->price_general,
            'price_company' => $this->price_company,
            'description'   => $this->description,
            'is_discount'   => $this->is_discount,
            'max_descount'  => $this->max_descount,
            'is_gift'       => $this->is_gift,
            'available'     => $this->available,
            'status'        => $this->status,
            'status_stok'   => $this->status_stok,
            'warranty_day'  => $this->warranty_day,
            'tax_selected'  => $this->tax_selected,
            'importe_iva'   => $this->importe_iva,
            'sku'           => $this->sku,
            'category'      => $this->category->title,
            'imagen'        => $this->product_imagen,
            "warehouses"    => $this->warehouses->sortByDesc("id")->map(function ($warehouse){
                return [
                    'id'            => $warehouse->id,
                    'warehouse_id'  => $warehouse->warehouse_id,
                    'unit_id'       => $warehouse->unit_id,
                    'stock'         => $warehouse->stock,
                    'umbral'        => $warehouse->umbral,
                    'warehouse'     => $warehouse->warehouse->name,
                    'unit'          => $warehouse->unit->name,
                ];
            })->values()->all(),
            "wallets"       => $this->wallets->sortByDesc("id")->map(function ($wallet){
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
            })->values()->all(),
            'created_at'    => $this->created_at->format("Y-m-d h:i A"),
        ];
    }
}
