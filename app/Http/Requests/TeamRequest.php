<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-admin');
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'members' => $this->input('members', []),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('teams', 'name')->ignore($this->route('team')?->id)],
            'description' => ['nullable', 'string', 'max:5000'],
            'sector_id' => ['nullable', 'integer', 'exists:sectors,id'],
            'is_active' => ['required', 'boolean'],
            'members' => ['nullable', 'array'],
            'members.*' => ['integer', 'distinct', 'exists:users,id'],
        ];
    }
}
