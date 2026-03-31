<?php

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Concerns\ValidatesTurnstile;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    use ValidatesTurnstile;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
            'turnstile_token' => $this->turnstileRules(),
        ];
    }

    /**
     * @return array<int, Closure>
     */
    public function after(): array
    {
        return $this->turnstileValidationHooks();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->turnstileMessages();
    }
}
