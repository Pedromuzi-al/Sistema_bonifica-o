<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class CycleScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-admin');
    }

    public function validationData(): array
    {
        $data = parent::validationData();
        $timezone = $data['timezone'] ?? null;
        if (! is_string($timezone) || ! in_array($timezone, \DateTimeZone::listIdentifiers(\DateTimeZone::ALL_WITH_BC), true)) {
            return $data;
        }
        foreach (['starts_at', 'ends_at'] as $field) {
            $value = $data[$field] ?? null;
            if (! is_string($value) || ! preg_match('/\A\d{4}-\d{2}-\d{2}T\d{2}:\d{2}\z/', $value)) {
                continue;
            }
            try {
                $date = Carbon::createFromFormat('!Y-m-d\TH:i', $value, $timezone);
                if ($date->format('Y-m-d\TH:i') === $value) {
                    $data[$field] = $date->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s');
                }
            } catch (\Throwable) {
                // Let the date validator report the original invalid input.
            }
        }

        return $data;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'timezone' => ['sometimes', 'required', 'timezone'],
            'starts_at' => ['required', 'date', 'after:now'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
        ];
    }

    public function messages(): array
    {
        return [
            'starts_at.after' => 'O início precisa ser depois de agora. Pode ser hoje: escolha um horário futuro e abra o ciclo antes dele.',
            'ends_at.after' => 'O fim precisa ser depois do início. Os dois horários podem ser no mesmo dia.',
        ];
    }
}
