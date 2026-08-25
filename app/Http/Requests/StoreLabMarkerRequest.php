<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLabMarkerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:lab_markers,name'],
            'unit' => ['required', 'string', 'max:50'],
            'norm_min' => ['nullable', 'numeric'],
            'norm_max' => ['nullable', 'numeric', 'gte:norm_min'],
        ];
    }
}
