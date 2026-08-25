<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['nullable', 'uuid', 'exists:pgsql.directory.categories,id'],
            'city_id' => ['nullable', 'uuid', 'exists:pgsql.directory.cities,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:20', 'max:5000'],
            'budget_currency' => ['nullable', 'string', 'size:3'],
            'budget_min' => ['nullable', 'numeric', 'min:0'],
            'budget_max' => ['nullable', 'numeric', 'gte:budget_min'],
            'required_by' => ['nullable', 'date', 'after_or_equal:today'],
            'contact_name' => ['required_without:customer_user_id', 'nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'preferred_contact' => ['required', 'in:email,phone,whatsapp'],
            'business_ids' => ['nullable', 'array', 'max:10'],
            'business_ids.*' => ['uuid', 'exists:pgsql.directory.businesses,id'],
        ];
    }
}
