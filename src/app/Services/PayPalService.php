<?php

namespace App\Services;

use Lunar\Models\Cart;
use Lunar\Paypal\Facades\Paypal;

/**
 * Wraps the Lunar PayPal driver for the SPA checkout flow.
 *
 * Builds PayPal orders with SPA-friendly redirect URLs (instead of
 * Laravel named routes) and delegates capture/refund to Lunar's driver.
 */
class PayPalService
{
    /**
     * Create a PayPal order from a calculated Lunar cart.
     *
     * Returns the PayPal order data including the approval URL.
     *
     * @return array{id: string, status: string, approve_url: string|null}
     */
    public function createOrder(Cart $cart, ?int $amountInCents = null): array
    {
        $cart = $cart->calculate();

        $shippingAddress = $cart->shippingAddress;
        $billingAddress = $cart->billingAddress ?: $shippingAddress;
        $frontendUrl = config('services.paypal.frontend_url', config('app.frontend_url', 'http://localhost:5173'));

        $requestData = [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'amount' => [
                        'currency_code' => $cart->currency->code,
                        'value' => number_format(($amountInCents ?? $cart->total->value) / 100, 2, '.', ''),
                    ],
                    'shipping' => [
                        'type' => 'SHIPPING',
                        'address' => [
                            'address_line_1' => $shippingAddress?->line_one,
                            'admin_area_2' => $shippingAddress?->city,
                            'admin_area_1' => $shippingAddress?->state,
                            'postal_code' => $shippingAddress?->postcode,
                            'country_code' => $shippingAddress?->country?->iso2 ?? 'PH',
                        ],
                    ],
                ],
            ],
            'payment_source' => [
                'paypal' => [
                    'experience_context' => [
                        'user_action' => 'PAY_NOW',
                        'shipping_preference' => 'SET_PROVIDED_ADDRESS',
                        'payment_method_preference' => 'IMMEDIATE_PAYMENT_REQUIRED',
                        'return_url' => $frontendUrl.'/checkout/success',
                        'cancel_url' => $frontendUrl.'/checkout',
                    ],
                ],
            ],
        ];

        $response = Paypal::baseHttpClient()
            ->withToken(Paypal::getAccessToken())
            ->withBody(json_encode($requestData), 'application/json')
            ->post('v2/checkout/orders')
            ->json();

        $approveUrl = collect($response['links'] ?? [])
            ->firstWhere('rel', 'payer-action')['href'] ?? null;

        return [
            'id' => $response['id'] ?? null,
            'status' => $response['status'] ?? null,
            'approve_url' => $approveUrl,
        ];
    }

    /**
     * Capture a previously approved PayPal order.
     *
     * @return array{id: string, status: string, payer: array|null}
     */
    public function captureOrder(string $paypalOrderId): array
    {
        return Paypal::capture($paypalOrderId);
    }

    /**
     * Retrieve a PayPal order by ID.
     *
     * @return array<string, mixed>
     */
    public function getOrder(string $paypalOrderId): array
    {
        return Paypal::getOrder($paypalOrderId);
    }
}
