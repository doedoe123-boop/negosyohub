<?php

namespace App\Http\Requests;

use App\DeliveryStatus;
use App\ShipmentProvider;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ManageShipmentRequest extends FormRequest
{
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
            'provider' => ['nullable', 'string', Rule::in(array_map(
                static fn (ShipmentProvider $provider): string => $provider->value,
                ShipmentProvider::cases()
            ))],
            'external_reference' => ['nullable', 'string', 'max:255'],
            'delivery_status' => ['nullable', 'string', Rule::in(array_map(
                static fn (DeliveryStatus $status): string => $status->value,
                DeliveryStatus::cases()
            ))],
            'driver_name' => ['nullable', 'string', 'max:255'],
            'driver_contact' => ['nullable', 'string', 'max:255'],
            'vehicle_type' => ['nullable', 'string', 'max:100'],
            'tracking_url' => ['nullable', 'url', 'max:500'],
            'pickup_address' => ['nullable', 'string', 'max:1000'],
            'dropoff_address' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
