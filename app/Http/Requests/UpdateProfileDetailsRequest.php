<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileDetailsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'age' => ['nullable', 'integer', 'min:10', 'max:120'],
            'height_cm' => ['nullable', 'integer', 'min:100', 'max:250'],
            'weight_goal_kg' => ['nullable', 'numeric', 'min:30', 'max:300'],
            'injuries' => ['nullable', 'string', 'max:2000'],
            'dietary_preferences' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
