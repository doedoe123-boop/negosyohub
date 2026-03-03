<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates a customer property inquiry submission.
 */
class SubmitInquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Inquiries are public — no login required
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'message' => ['nullable', 'string', 'max:1000'],
            'source' => ['nullable', 'string', 'in:website,referral,walk-in,social_media'],
        ];
    }
}
