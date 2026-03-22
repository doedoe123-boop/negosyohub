<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'notification_preferences' => ['sometimes', 'array'],
            'notification_preferences.order_updates' => ['boolean'],
            'notification_preferences.promotions' => ['boolean'],
            'preferred_locale' => [
                'sometimes',
                'nullable',
                'string',
                Rule::exists('lunar_languages', 'code')->where(
                    fn ($query) => $query->where('is_active', true)
                ),
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'preferred_locale.exists' => 'The selected language is not available.',
        ];
    }
}
