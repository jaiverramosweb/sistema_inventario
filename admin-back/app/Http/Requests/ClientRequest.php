<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClientRequest extends FormRequest
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
            'name'              => 'required',
            'surname'           => 'nullable',
            'email'             => 'nullable',
            'phone'             => 'nullable',
            'type_client'       => 'nullable',
            'type_document'     => 'required',
            'n_document'        => 'required',
            'date_birthday'     => 'nullable',
            'gender'            => 'nullable',
            'status'            => 'nullable',
            'id_department'     => 'nullable',
            'id_municipality'   => 'nullable',
            'id_district'       => 'nullable',
            'department'        => 'nullable',
            'municipality'      => 'nullable',
            'district'          => 'nullable',
            'address'           => 'nullable',
        ];
    }
}
