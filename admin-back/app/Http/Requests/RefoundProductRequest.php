<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RefoundProductRequest extends FormRequest
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
            'sale_id' => 'required|exists:sales,id',
            'sale_detail_id' => 'required|exists:sale_details,id',
            'type' => 'required',
            'quantity' => 'required|integer|min:1',
            'description' => 'nullable|string',
        ];
    }
}
