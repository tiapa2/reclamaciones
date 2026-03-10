<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDoctorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
{
    return [
        'rnc' => ['required', 'string', 'max:50', 'unique:doctors,rnc'],
        'full_name' => ['required', 'string', 'max:255'],
        'specialty' => ['nullable', 'string', 'max:255'],
        'institution' => ['nullable', 'string', 'max:255'],
        'phone' => ['nullable', 'string', 'max:50'],
        'email' => ['nullable', 'email', 'max:255'],

        // insurers[insurerId] = doctor_code
        'insurers' => ['nullable', 'array'],
        'insurers.*' => ['nullable', 'string', 'max:50'],

        // ✅ NCF por tipo (doctor)
'ncf' => ['nullable', 'array'],
'ncf.*' => ['nullable', 'array'],
'ncf.*.active' => ['nullable', 'boolean'],
'ncf.*.from_number' => ['nullable', 'integer'],
'ncf.*.to_number' => ['nullable', 'integer'],
'ncf.*.requested_at' => ['nullable', 'date'],
'ncf.*.expires_at' => ['nullable', 'date'],

    ];
}

}
