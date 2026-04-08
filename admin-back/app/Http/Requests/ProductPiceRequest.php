<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductPiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'product_id'    => 'required|integer|exists:products,id',
            'type_client'   => 'required|integer|in:1,2',
            'unit_id'       => 'required|integer|exists:units,id',
            'sucursal_id'   => 'nullable|integer|exists:sucursales,id',
            'price'         => 'required|numeric|min:0',
        ];
    }
}
