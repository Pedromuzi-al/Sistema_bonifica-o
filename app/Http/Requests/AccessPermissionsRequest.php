<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AccessPermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-admin');
    }

    public function rules(): array
    {
        return [
            'role' => ['nullable', 'string', Rule::exists('roles', 'name')->where('guard_name', 'web')],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'distinct', Rule::exists('permissions', 'name')->where('guard_name', 'web')],
        ];
    }
}
