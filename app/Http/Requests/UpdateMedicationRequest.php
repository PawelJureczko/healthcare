<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMedicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('medication')->user_id === $this->user()->id;
    }

    public function rules(): array
    {
        return [
            'stopped_at' => ['nullable', 'date'],
        ];
    }
}
