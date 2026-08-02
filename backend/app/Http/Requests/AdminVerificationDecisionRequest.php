<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminVerificationDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', 'in:approved,rejected,information_requested'],
            'approved_level_code' => [
                'nullable',
                'required_if:decision,approved',
                'in:contact_verified,document_verified,location_verified,trusted_business',
            ],
            'reason' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
