<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBusinessSocialLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'platform' => ['required', 'in:facebook,instagram,linkedin,tiktok,youtube,x,telegram'],
            'url' => ['required', 'url', 'max:500'],
        ];
    }
}
