<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cart_id' => ['required', 'uuid'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'coupon_code' => ['nullable', 'string', 'max:100'],
            'shipping_address' => ['nullable', 'array'],
            'billing_address' => ['nullable', 'array'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
