<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ResultRequest extends FormRequest
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
        return [
            'impact_summary' => ['required', 'string', 'max:10000'],
            'limitations' => ['nullable', 'string', 'max:10000'],
            'measurements' => ['required', 'array', 'min:1', 'max:20'],
            'measurements.*' => ['array:indicator,before,after,unit,period_start,period_end,evidence,qualitative'],
            'measurements.*.indicator' => ['required', 'string', 'max:255'],
            'measurements.*.before' => ['nullable', 'numeric'],
            'measurements.*.after' => ['nullable', 'numeric'],
            'measurements.*.unit' => ['required', 'string', 'max:100'],
            'measurements.*.period_start' => ['required', 'date'],
            'measurements.*.period_end' => ['required', 'date', 'before_or_equal:today', 'after_or_equal:measurements.*.period_start'],
            'measurements.*.evidence' => ['required', 'string', 'max:5000'],
            'measurements.*.qualitative' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
