<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class EvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('evaluate', $this->route('idea'));
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
        return ['scores' => ['required', 'array', 'min:1'], 'scores.*' => ['required', 'integer', 'between:1,5'], 'notes' => ['required', 'string', 'max:10000'], 'recommendation' => ['required', 'in:approve,reject,clarify']];
    }
}
