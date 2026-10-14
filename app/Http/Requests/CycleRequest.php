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
            'max_awards' => ['required', 'integer', 'min:1', 'max:1000'],
        ];
    }
}
