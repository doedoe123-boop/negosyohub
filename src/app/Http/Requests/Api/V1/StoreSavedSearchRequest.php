<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreSavedSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'criteria' => ['required', 'array'],
            'criteria.*' => ['nullable', 'string'],
            'notify_frequency' => ['nullable', 'string', 'in:never,daily,weekly'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
