<?php

namespace App\Http\Requests\Api\V1;

use App\MovingBookingStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateMovingBookingStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', new Enum(MovingBookingStatus::class)],
        ];
    }
}
