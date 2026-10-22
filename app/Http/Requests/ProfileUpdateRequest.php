<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $target = $this->route('user');

        return ! $target || $target->id === $this->user()->id || $this->user()->can('manage-admin');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $profileUser = $this->route('user');
        $profileUser = $profileUser instanceof User ? $profileUser : $this->user();

        return [
            'current_password' => ['bail', Rule::requiredIf($profileUser->id === $this->user()->id && $this->input('email') !== $profileUser->email), 'nullable', 'string', 'current_password'],
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($profileUser->id),
            ],
        ];
    }
}
