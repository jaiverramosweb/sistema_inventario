<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaleRequest extends FormRequest
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
            'client_id' => 'nullable',
            'type_client' => 'nullable',
            'discount' => 'nullable',
            'subtotal' => 'nullable',
            'total' => 'nullable',
            'iva' => 'nullable',
            'state' => 'nullable',
            'state_mayment' => 'nullable',
            'debt' => 'nullable',
            'paid_out' => 'nullable',
            'date_completed' => 'nullable',
            'description' => 'nullable',
            'sale_details' => 'nullable|array',
            'payments' => 'nullable|array',
        ];
    }
}
