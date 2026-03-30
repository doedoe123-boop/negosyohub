<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class CreateMovingBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'store_id' => ['required', 'integer', 'exists:stores,id'],
            'rental_agreement_id' => ['nullable', 'integer', 'exists:rental_agreements,id'],
            'pickup_address' => ['required', 'string', 'max:500'],
            'delivery_address' => ['required', 'string', 'max:500'],
            'pickup_city' => ['required', 'string', 'max:100'],
            'delivery_city' => ['required', 'string', 'max:100'],
            'scheduled_at' => ['required', 'date', 'after:now'],
            'contact_name' => ['required', 'string', 'max:255'],
            'contact_phone' => ['required', 'string', 'max:30'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'add_on_ids' => ['nullable', 'array'],
            'add_on_ids.*' => ['integer', 'exists:moving_add_ons,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'scheduled_at.after' => 'The scheduled date must be in the future.',
            'store_id.exists' => 'The selected moving company does not exist.',
        ];
    }
}
