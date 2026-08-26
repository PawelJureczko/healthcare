<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGymWorkoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'exercises' => ['required', 'array', 'min:1'],
            'exercises.*.exercise_id' => ['required', 'integer', 'exists:exercises,id'],
            'exercises.*.sets' => ['required', 'array', 'min:1'],
            'exercises.*.sets.*.planned_weight_kg' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'exercises.*.sets.*.planned_reps' => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }
}
