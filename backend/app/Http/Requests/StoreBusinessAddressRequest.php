<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBusinessAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'administrative_area_id' => [
                'required',
                'uuid',
                'exists:pgsql.directory.administrative_areas,id',
            ],
            'city_id' => [
                'required',
                'uuid',
                'exists:pgsql.directory.cities,id',
            ],
            'district_id' => [
                'nullable',
                'uuid',
                'exists:pgsql.directory.districts,id',
            ],
            'address_line1' => [
                'required',
                'string',
                'max:500',
            ],
            'address_line2' => [
                'nullable',
                'string',
                'max:500',
            ],
            'landmark' => [
                'nullable',
                'string',
                'max:500',
            ],
            'postal_code' => [
                'nullable',
                'string',
                'max:50',
            ],
        ];
    }
}
