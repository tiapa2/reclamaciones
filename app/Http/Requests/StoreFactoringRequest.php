<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFactoringRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Ajusta si usas policies
    }

    public function rules(): array
    {
        return [
            'invoice_id' => ['required','integer','exists:invoices,id'],
            'purchase_date' => ['required','date'],
            'term_days' => ['required','integer','min:1','max:365'],
            'discount_rate' => ['required','numeric','min:0','max:100'],
            'commission_rate' => ['required','numeric','min:0','max:100'],
            'reference_no' => ['nullable','string','max:120'],
            'notes' => ['nullable','string','max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'invoice_id.required' => 'Debe seleccionar una factura.',
        ];
    }
}
