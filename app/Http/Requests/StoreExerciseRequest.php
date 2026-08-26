<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExerciseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'unique:exercises,name'],
            'muscle_group' => ['required', 'string', 'max:50'],
            'lumbar_risk' => ['required', 'boolean'],
        ];
    }
}
