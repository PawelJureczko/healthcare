<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLabResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'performed_at' => ['required', 'date', 'before_or_equal:today'],
            'note' => ['nullable', 'string', 'max:2000'],
            'values' => ['required', 'array', 'min:1'],
            'values.*.lab_marker_id' => ['required', 'integer', 'exists:lab_markers,id'],
            'values.*.value' => ['required', 'numeric', 'min:0', 'max:99999'],
        ];
    }
}
