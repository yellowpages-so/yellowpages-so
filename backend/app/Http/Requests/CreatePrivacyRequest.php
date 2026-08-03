<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreatePrivacyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'request_type' => ['required', 'in:access,export,rectification,deletion,restriction,objection'],
            'email' => ['required', 'email', 'max:255'],
            'details' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
