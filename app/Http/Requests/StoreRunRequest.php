<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRunRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date', 'before_or_equal:today'],
            'distance_km' => ['required', 'numeric', 'min:0.1', 'max:200'],
            'duration_min' => ['required', 'numeric', 'min:1', 'max:600'],
            'avg_heart_rate' => ['nullable', 'integer', 'min:60', 'max:220'],
            'comment' => ['nullable', 'string', 'max:500'],
            'wellbeing_rating' => ['nullable', 'integer', 'min:1', 'max:5'],
        ];
    }
}
