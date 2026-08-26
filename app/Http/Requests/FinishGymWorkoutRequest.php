<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FinishGymWorkoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'back_pain_rating' => ['required', 'integer', 'min:0', 'max:10'],
            'wellbeing_rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:500'],
        ];
    }
}
