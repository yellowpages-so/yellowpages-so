<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateWebhookSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'api_client_id' => ['required', 'uuid'],
            'event_code' => ['required', 'string', 'max:150'],
            'endpoint_url' => ['required', 'url', 'max:2000'],
            'max_attempts' => ['nullable', 'integer', 'min:1', 'max:10'],
        ];
    }
}
