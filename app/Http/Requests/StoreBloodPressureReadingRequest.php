<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBloodPressureReadingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'measured_at' => ['required', 'date', 'before_or_equal:now'],
            'systolic' => ['required', 'integer', 'min:60', 'max:260'],
            'diastolic' => ['required', 'integer', 'min:40', 'max:200'],
            'resting_pulse' => ['nullable', 'integer', 'min:30', 'max:220'],
        ];
    }
}
