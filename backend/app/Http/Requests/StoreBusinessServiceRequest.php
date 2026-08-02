<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBusinessServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'service_id' => ['nullable', 'uuid', 'exists:directory.services,id'],
            'custom_name' => ['nullable', 'required_without:service_id', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
            'price_from' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
        ];
    }
}
