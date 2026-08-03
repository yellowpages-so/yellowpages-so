<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreatePaymentIntentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'business_id' => ['required', 'uuid'],
            'order_id' => ['nullable', 'uuid'],
            'invoice_id' => ['nullable', 'uuid'],
            'provider_code' => ['required', 'string', 'max:100'],
            'currency' => ['required', 'string', 'size:3'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
