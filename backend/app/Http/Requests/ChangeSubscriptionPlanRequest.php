<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class ChangeSubscriptionPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
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
            'effective' => ['required', 'in:immediately,next_period'],
        ];
    }
}
