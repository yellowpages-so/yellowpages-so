<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBusinessTeamMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'uuid', 'exists:iam.users,id'],
            'role_code' => ['required', 'in:owner,manager,editor,analyst'],
        ];
    }
}
