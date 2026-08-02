<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'requested_level_code' => [
                'required',
                'in:contact_verified,document_verified,location_verified,trusted_business',
            ],
        ];
    }
}
