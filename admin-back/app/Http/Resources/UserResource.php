<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'name'          => $this->name,
            'email'         => $this->email,
            'role_id'       => (int) $this->role_id,
            'role'          => $this->role->name,
            'avatar'        => $this->avatar ? env('APP_URL') . 'storage/' . $this->avatar : null,
            'sucuarsal_id'  => (int) $this->sucuarsal_id,
            'sucursale'     => $this->sucursale->name,
            'phone'         => $this->phone,
            'type_document' => $this->type_document,
            'document'      => $this->document,
            'gender'        => $this->gender,
            'created_at'    => $this->created_at->format("Y-m-d h:i A")
        ];
    }
}
