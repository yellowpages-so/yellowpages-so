<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLeadStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:new,viewed,contacted,quoted,won,lost,closed'],
            'note' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
