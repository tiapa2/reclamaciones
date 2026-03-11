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
        $isArs = $this->input('invoice_type', 'ars') === 'ars';

        return [
            'doctor_id'    => ['required', 'integer', 'exists:doctors,id'],
            'insurer_id'   => ['required', 'integer', 'exists:insurers,id'],
            'ncf_type_id'  => ['required', 'integer', 'exists:ncf_types,id'],
            'invoice_date' => ['required', 'date'],
            'invoice_type' => ['required', 'in:ars,clinica'],

            'items'                    => ['required', 'array', 'min:1'],
            'items.*.service_date'     => ['required', 'date'],
            'items.*.amount'           => ['required', 'numeric', 'min:0.01'],

            // Campos ARS
            'items.*.patient_name'     => $isArs
                ? ['required', 'string', 'max:255']
                : ['nullable', 'string', 'max:255'],
            'items.*.affiliate_no'     => ['nullable', 'string', 'max:100'],
            'items.*.authorization_no' => $isArs
                ? ['required', 'string', 'max:100']
                : ['nullable', 'string', 'max:100'],
            'items.*.procedure_id'     => ['nullable', 'integer'],

            // Campo clínica
            'items.*.description'      => $isArs
                ? ['nullable', 'string', 'max:500']
                : ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required'                    => 'Debes agregar al menos un registro.',
            'items.min'                         => 'Debes agregar al menos un registro.',
            'items.*.patient_name.required'     => 'El nombre del paciente es obligatorio.',
            'items.*.authorization_no.required' => 'El número de autorización es obligatorio.',
            'items.*.description.required'      => 'La descripción es obligatoria.',
        ];
    }
}
