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
            'created_at'    => $this->created_at->format("Y-m-d h:i A"),
        ];
    }
}
