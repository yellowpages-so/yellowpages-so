<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminUpdateBusinessStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:draft,pending_review,published,suspended,rejected,closed'],
            'reason' => ['required', 'string', 'max:2000'],
        ];
    }
}
