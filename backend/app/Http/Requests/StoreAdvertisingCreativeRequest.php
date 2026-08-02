<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class StoreAdvertisingCreativeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'placement_id' => [
                'required',
                'uuid',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! DB::table('advertising.placements')
                        ->where('id', $value)
                        ->where('active', true)
                        ->exists()) {
                        $fail('The selected advertising placement does not exist.');
                    }
                },
            ],
            'headline' => ['required', 'string', 'max:120'],
            'body' => ['nullable', 'string', 'max:500'],
            'image_url' => ['nullable', 'url', 'max:1000'],
            'destination_url' => ['required', 'url', 'max:1000'],
            'call_to_action' => ['required', 'string', 'max:50'],
        ];
    }
}
