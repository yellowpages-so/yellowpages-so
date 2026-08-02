<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendCommunicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'event_code' => ['required', 'string', 'max:100'],
            'user_id' => ['nullable', 'uuid'],
            'business_id' => ['nullable', 'uuid'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'device_token' => ['nullable', 'string', 'max:2000'],
            'channels' => ['required', 'array', 'min:1'],
            'channels.*' => ['in:email,sms,whatsapp,push,in_app'],
            'variables' => ['nullable', 'array'],
        ];
    }
}
