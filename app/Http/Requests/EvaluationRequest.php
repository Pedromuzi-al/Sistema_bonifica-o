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

    public function messages(): array
    {
        return [
            'scores.required' => 'Informe as notas dos critérios técnicos. Se os campos de notas não aparecem, cadastre critérios ativos em Administração.',
            'scores.*.required' => 'Preencha a nota de todos os critérios técnicos.',
            'scores.*.between' => 'Cada nota técnica deve estar entre 1 e 5.',
        ];
    }

    public function rules(): array
    {
        return ['scores' => ['required', 'array', 'min:1'], 'scores.*' => ['required', 'integer', 'between:1,5'], 'notes' => ['required', 'string', 'max:10000'], 'recommendation' => ['required', 'in:approve,reject,clarify']];
    }
}
