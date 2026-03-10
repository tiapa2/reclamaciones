<?php

// app/Http/Requests/StoreInvoicePaymentRequest.php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvoicePaymentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'invoice_id' => ['nullable','exists:invoices,id'],
            'payment_date' => ['required','date'],
            'amount' => ['required','numeric','min:0.01'],
            'method' => ['required','in:cash,transfer,card,check,other'],
            'reference_no' => ['nullable','string','max:80'],
            'notes' => ['nullable','string','max:1000'],
        ];
    }
}
