<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class StoreAdvertisingCampaignRequest extends FormRequest
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
                    if (! DB::table('directory.businesses')
                        ->where('id', $value)
                        ->exists()) {
                        $fail('The selected business does not exist.');
                    }
                },
            ],
            'name' => ['required', 'string', 'max:255'],
            'objective' => ['required', 'in:visibility,traffic,leads'],
            'billing_model' => ['required', 'in:fixed,cpc,cpm'],
            'total_budget' => ['required', 'numeric', 'min:1'],
            'daily_budget' => ['nullable', 'numeric', 'min:1', 'lte:total_budget'],
            'currency' => ['required', 'string', 'size:3'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'targeting' => ['nullable', 'array'],
        ];
    }
}
