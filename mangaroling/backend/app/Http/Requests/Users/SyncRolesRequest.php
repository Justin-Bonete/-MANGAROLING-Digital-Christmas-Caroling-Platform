<?php

namespace App\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;

class SyncRolesRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', 'distinct', 'exists:roles,name'],
        ];
    }
}
