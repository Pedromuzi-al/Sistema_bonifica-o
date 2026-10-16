<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CycleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-admin');
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
            'starts_at.after_or_equal' => 'Escolha uma data e um horário de início futuros. Você precisa de tempo para selecionar as ideias e abrir o ciclo.',
            'ends_at.after' => 'O fim da votação deve ser depois do início.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nome do ciclo',
            'starts_at' => 'início da votação',
            'ends_at' => 'fim da votação',
        ];
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'starts_at' => ['required', 'date', 'after_or_equal:now'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'weights' => ['required', 'array:real_results,technical,votes'],
            'weights.real_results' => ['required', 'numeric', 'min:0', 'max:100'],
            'weights.technical' => ['required', 'numeric', 'min:0', 'max:100'],
            'weights.votes' => ['required', 'numeric', 'min:0', 'max:100'],
            'criteria' => ['required', 'array', 'min:1', 'max:20'],
            'criteria.*' => ['array:name,description,weight'],
            'criteria.*.name' => ['required', 'string', 'max:255'],
            'criteria.*.description' => ['required', 'string', 'max:1000'],
            'criteria.*.weight' => ['required', 'numeric', 'gt:0', 'max:100'],
            'result_indicators' => ['required', 'array', 'min:1', 'max:20'],
            'result_indicators.*' => ['array:indicator,unit,direction,target'],
            'result_indicators.*.indicator' => ['required', 'string', 'max:255'],
            'result_indicators.*.unit' => ['required', 'string', 'max:100'],
            'result_indicators.*.direction' => ['required', 'in:increase,decrease,qualitative'],
            'result_indicators.*.target' => ['required', 'numeric', 'gt:0'],
            'minimum_votes' => ['required', 'integer', 'min:1', 'max:100000'],
            'allow_abstention' => ['required', 'boolean'],
            'category' => ['required', 'string', 'max:255'],
            'minimum_score' => ['required', 'numeric', 'between:0,100'],
            'reward_amount' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'anomaly_threshold' => ['sometimes', 'integer', 'min:2', 'max:100000'],
            'tie_break_order' => ['sometimes', 'array', 'size:3'],
            'tie_break_order.*' => ['required', 'distinct', 'in:real_results,technical,votes'],
            'categories' => ['sometimes', 'array', 'max:20'],
            'categories.*' => ['array:name,minimum_score,maximum_score,reward_amount,max_awards'],
            'categories.*.name' => ['required', 'string', 'max:255', 'distinct'],
            'categories.*.minimum_score' => ['required', 'numeric', 'between:0,100'],
            'categories.*.maximum_score' => ['required', 'numeric', 'between:0,100', 'gte:categories.*.minimum_score'],
            'categories.*.reward_amount' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'categories.*.max_awards' => ['required', 'integer', 'min:1', 'max:1000'],
            'max_awards' => ['required', 'integer', 'min:1', 'max:1000'],
        ];
    }
}
