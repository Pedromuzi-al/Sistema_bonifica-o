<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BonusPolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-admin');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'reward_amount' => ['required', 'numeric', 'min:0', 'max:9999999999', 'decimal:0,2'],
            'minimum_score' => ['required', 'numeric', 'between:0,100', 'decimal:0,2'],
            'allow_abstention' => ['required', 'boolean'],
            'anomaly_threshold' => ['required', 'integer', 'between:2,100000'],
            'active' => ['required', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['reward_amount' => 'valor por ideia', 'minimum_score' => 'nota mínima', 'anomaly_threshold' => 'limite de votos idênticos'];
    }
}
