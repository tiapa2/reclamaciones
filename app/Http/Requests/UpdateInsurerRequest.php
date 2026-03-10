<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInsurerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('insurer')?->id;

        return [
            'name'  => ['required', 'string', 'max:150'],
            'rnc'   => ['required', 'string', 'max:20', 'unique:insurers,rnc,' . $id],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
        ];
    }
}
