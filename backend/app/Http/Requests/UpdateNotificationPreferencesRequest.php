<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationPreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'event_code' => ['required', 'string', 'max:100'],
            'email_enabled' => ['boolean'],
            'sms_enabled' => ['boolean'],
            'whatsapp_enabled' => ['boolean'],
            'push_enabled' => ['boolean'],
            'in_app_enabled' => ['boolean'],
        ];
    }
}
