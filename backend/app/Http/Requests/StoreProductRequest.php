<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class StoreProductRequest extends FormRequest
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
            'type' => ['required', 'in:product,service,package,digital'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:5000'],
            'sku' => ['nullable', 'string', 'max:100'],
            'currency' => ['required', 'string', 'size:3'],
            'price' => ['required', 'numeric', 'min:0'],
            'compare_at_price' => ['nullable', 'numeric', 'gte:price'],
            'taxable' => ['boolean'],
            'track_inventory' => ['boolean'],
            'digital' => ['boolean'],
            'quantity_on_hand' => ['nullable', 'integer', 'min:0'],
            'allow_backorder' => ['boolean'],
            'attributes' => ['nullable', 'array'],
        ];
    }
}
