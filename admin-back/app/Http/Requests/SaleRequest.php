<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

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
            'type_client' => 'nullable|integer|in:1,2',
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

    public function messages(): array
    {
        return [
            'type_client.integer' => 'El tipo de cliente debe ser numerico.',
            'type_client.in' => 'El tipo de cliente debe ser 1 (cliente final) o 2 (cliente empresa).',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'status' => 422,
            'code' => 'VALIDATION_ERROR',
            'message' => 'Los datos enviados no son validos.',
            'errors' => $validator->errors()->toArray(),
        ], 422));
    }
}
