<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBusinessContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'contact_type' => ['required', 'in:phone,whatsapp,email,website,fax'],
            'label' => ['nullable', 'string', 'max:100'],
            'value' => ['required', 'string', 'max:500'],
            'is_primary' => ['boolean'],
            'is_public' => ['boolean'],
        ];
    }
}
