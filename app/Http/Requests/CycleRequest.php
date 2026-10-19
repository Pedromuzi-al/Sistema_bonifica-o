<?php

namespace App\Http\Requests;

use Illuminate\Http\Exceptions\HttpResponseException;

class CycleRequest extends CycleScheduleRequest
{
    protected $redirectRoute = 'cycles.create';

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
        if ($this->input('purpose') === 'selection') {
            return [
                ...parent::rules(),
                'purpose' => ['required', 'in:selection'],
                'minimum_votes' => ['sometimes', 'integer', 'between:1,100000'],
                'minimum_score' => ['sometimes', 'numeric', 'between:0,100'],
                'max_selections' => ['sometimes', 'integer', 'between:1,1000'],
                'reward_amount' => ['sometimes', 'numeric', 'min:0', 'max:9999999999'],
                'allow_abstention' => ['sometimes', 'boolean'],
                'anomaly_threshold' => ['sometimes', 'integer', 'between:2,100000'],
                'criteria' => ['sometimes', 'array', 'min:1', 'max:20'],
                'criteria.*' => ['array:name,description,weight'],
                'criteria.*.name' => ['required', 'string', 'max:255', 'distinct'],
                'criteria.*.description' => ['required', 'string', 'max:1000'],
                'criteria.*.weight' => ['required', 'numeric', 'gt:0', 'max:100'],
            ];
        }

        return [
            'purpose' => ['sometimes', 'in:recognition'],
            ...parent::rules(),
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
            'categories.*' => ['array:name,minimum_score,maximum_score,reward_amount,max_awards,minimum_votes,minimum_technical,minimum_results,idea_category'],
            'categories.*.name' => ['required', 'string', 'max:255', 'distinct'],
            'categories.*.minimum_score' => ['required', 'numeric', 'between:0,100'],
            'categories.*.maximum_score' => ['required', 'numeric', 'between:0,100', 'gte:categories.*.minimum_score'],
            'categories.*.reward_amount' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'categories.*.minimum_votes' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'categories.*.minimum_technical' => ['nullable', 'numeric', 'between:0,100'],
            'categories.*.minimum_results' => ['nullable', 'numeric', 'between:0,100'],
            'categories.*.idea_category' => ['nullable', 'string', 'max:255'],
            'categories.*.max_awards' => ['required', 'integer', 'min:1', 'max:1000'],
            'max_awards' => ['required', 'integer', 'min:1', 'max:1000'],
        ];
    }
}
