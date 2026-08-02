<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class StartSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'business_id' => [
                'required',
                'uuid',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! DB::table('directory.businesses')->where('id', $value)->exists()) {
                        $fail('The selected business does not exist.');
                    }
                },
            ],
            'plan_code' => [
                'required',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! DB::table('billing.plans')
                        ->where('code', $value)
                        ->where('active', true)
                        ->exists()) {
                        $fail('The selected plan is unavailable.');
                    }
                },
            ],
            'coupon_code' => ['nullable', 'string', 'max:100'],
            'payment_provider' => ['nullable', 'in:manual,stripe,paypal,evc_plus,zaad,sahal,edahab'],
        ];
    }
}
