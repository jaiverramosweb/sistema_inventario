<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
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
            'name'              => $this->name,
            'surname'           => $this->surname,
            'email'             => $this->email,
            'phone'             => $this->phone,
            'type_client'       => $this->type_client,
            'type_document'     => $this->type_document,
            'n_document'        => $this->n_document,
            'date_birthday'     => $this->date_birthday,
            'user_id'           => $this->user_id,
            'user'              => $this->user->name,
            'sucursal_id'       => $this->sucursal_id,
            'sucursale'         => $this->sucursale->name,
            'gender'            => $this->gender,
            'status'            => $this->status,
            'id_department'     => $this->id_department,
            'id_municipality'   => $this->id_municipality,
            'id_district'       => $this->id_district,
            'department'        => $this->department,
            'municipality'      => $this->municipality,
            'district'          => $this->district,
            'address'           => $this->address,
            'created_at'        => $this->created_at->format("Y-m-d"),
        ];
    }
}
