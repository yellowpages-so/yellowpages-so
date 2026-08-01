<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBusinessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'legal_name' => ['sometimes', 'required', 'string', 'max:255'],
            'trading_name' => ['sometimes', 'required', 'string', 'max:255'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:10000'],
            'registration_number' => ['nullable', 'string', 'max:100'],
            'tax_number' => ['nullable', 'string', 'max:100'],
            'established_year' => ['nullable', 'integer', 'min:1800', 'max:'.now()->year],
            'website_url' => ['nullable', 'url', 'max:500'],
            'primary_city_id' => ['nullable', 'uuid', 'exists:directory.cities,id'],
        ];
    }
}
