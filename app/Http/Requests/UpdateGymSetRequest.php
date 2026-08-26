<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGymSetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'weight_kg' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'reps' => ['nullable', 'integer', 'min:0', 'max:100'],
            'status' => ['required', Rule::in(['pending', 'done', 'skipped'])],
        ];
    }
}
