<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReportReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'in:spam,abuse,conflict_of_interest,false_information,privacy,other'],
            'details' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
