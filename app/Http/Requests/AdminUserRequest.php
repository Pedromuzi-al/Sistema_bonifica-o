<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AdminUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-admin');
    }

    public function rules(): array
    {
        $target = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'lowercase', 'max:255', Rule::unique('users', 'email')->ignore($target?->id)],
            'password' => $target ? ['prohibited'] : ['required', 'confirmed', Password::defaults()],
            'sector_id' => ['nullable', 'integer', 'exists:sectors,id'],
            'teams' => ['nullable', 'array'],
            'teams.*' => ['integer', 'distinct', 'exists:teams,id'],
            'role' => ['nullable', 'string', Rule::exists('roles', 'name')->where('guard_name', 'web')],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'distinct', Rule::exists('permissions', 'name')->where('guard_name', 'web')],
        ];
    }
}
