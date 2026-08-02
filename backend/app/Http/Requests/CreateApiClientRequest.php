<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateApiClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'business_id' => ['nullable', 'uuid'],
            'name' => ['required', 'string', 'max:255'],
            'environment' => ['required', 'in:sandbox,production'],
            'scopes' => ['required', 'array', 'min:1'],
            'scopes.*' => [
                'string',
                Rule::in(config('developer.scopes')),
            ],
            'rate_limit_per_minute' => ['nullable', 'integer', 'min:1', 'max:5000'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }
}
