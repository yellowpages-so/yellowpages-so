<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UploadMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $maxKb = max(
            config('media.max_image_mb'),
            config('media.max_document_mb'),
            config('media.max_video_mb')
        ) * 1024;

        return [
            'file' => ['required', 'file', "max:{$maxKb}"],
            'owner_type' => ['required', 'in:business,review,advertising,verification,user'],
            'owner_id' => ['required', 'uuid'],
            'business_id' => ['nullable', 'uuid'],
            'collection' => [
                'required',
                Rule::in(config('media.collections')),
            ],
            'visibility' => ['required', 'in:public,private'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:1000'],
            'tags' => ['nullable', 'array', 'max:20'],
            'tags.*' => ['string', 'max:50'],
        ];
    }
}
