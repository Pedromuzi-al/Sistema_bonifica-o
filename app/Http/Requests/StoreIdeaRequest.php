<?php

namespace App\Http\Requests;

use App\Models\Idea;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreIdeaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Idea::class);
    }

    protected function failedAuthorization(): void
    {
        if ($this->expectsJson()) {
            parent::failedAuthorization();
        }
        throw new HttpResponseException(redirect()->back()->with('permission_error', 'Você não tem permissão para realizar esta ação.'));
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'problem_description' => ['required', 'string', 'max:20000'],
            'solution_description' => ['required', 'string', 'max:20000'],
            'origin_sector_id' => ['required', 'integer', 'exists:sectors,id'],
            'beneficiary_sectors' => ['required', 'array', 'min:1'],
            'beneficiary_sectors.*' => ['integer', 'distinct', 'exists:sectors,id'],
            'expected_results' => ['required', 'string', 'max:20000'],
            'resources_needed' => ['required', 'string', 'max:20000'],
            'additional_information' => ['nullable', 'string', 'max:20000'],
        ];
    }
}
