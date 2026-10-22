<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class TechnicalModelRequest extends FormRequest
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
            'active' => ['required', 'boolean'],
            'criteria' => ['required', 'array', 'min:1', 'max:20'],
            'criteria.*' => ['array:name,description,weight'],
            'criteria.*.name' => ['required', 'string', 'max:255', 'distinct:ignore_case'],
            'criteria.*.description' => ['required', 'string', 'max:1000'],
            'criteria.*.weight' => ['required', 'numeric', 'gt:0', 'max:100', 'decimal:0,2'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isEmpty() && abs(array_sum(array_column($this->input('criteria'), 'weight')) - 100) > 0.001) {
                $validator->errors()->add('criteria', 'Os pesos das perguntas devem somar 100%.');
            }
        }];
    }
}
