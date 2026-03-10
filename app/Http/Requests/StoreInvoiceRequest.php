<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'doctor_id'   => ['required','integer','exists:doctors,id'],
            'insurer_id'  => ['required','integer','exists:insurers,id'],
            'ncf_type_id' => ['required','integer','exists:ncf_types,id'],
            'invoice_date'=> ['required','date'],

            // items
            'items' => ['required','array','min:1'],
            'items.*.service_date'     => ['required','date'],
            'items.*.patient_name'     => ['required','string','max:255'],
            'items.*.affiliate_no'     => ['required','string','max:100'],
            'items.*.authorization_no' => ['required','string','max:100'],
            'items.*.procedure_id'     => ['nullable','integer'], // si luego creas procedures, lo cambiamos a exists
            'items.*.amount'           => ['required','numeric','min:0.01'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Debes agregar al menos un registro.',
            'items.min'      => 'Debes agregar al menos un registro.',
        ];
    }
}
