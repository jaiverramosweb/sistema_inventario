<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UnitConversionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'unit_id' => $this->unit_id,
            'unit' => $this->unit->name,
            'unit_to_id' => $this->unit_to_id,
            'unit_to' => $this->unit_to->name,
            'created_at' => $this->created_at->format("Y-m-d"),
        ];
    }
}
