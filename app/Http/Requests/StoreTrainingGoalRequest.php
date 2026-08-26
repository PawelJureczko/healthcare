<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTrainingGoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'target_distance_km' => ['required', 'numeric', 'min:0.1', 'max:500'],
            'target_date' => ['required', 'date', 'after:today'],
            'target_time_min' => ['nullable', 'numeric', 'min:1'],
        ];
    }
}
