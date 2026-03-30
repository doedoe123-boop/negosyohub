<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\GlobalSeoSetting;
use App\Models\Order;
use App\Models\Store;
use App\OrderPaymentMethod;
use App\OrderPaymentStatus;
use App\OrderStatus;
use App\Services\MarketplaceCartService;
use App\Services\MultiStoreCheckoutService;
use App\Services\OrderService;
use App\Services\PaymentManager;
use App\Services\PayMongoService;
use App\Services\PayPalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Lunar\Facades\CartSession;

/**
 * Handles payment operations for the customer checkout flow.
 *
 * Supports two payment providers:
 *   - PayMongo: intent-based flow (create order → create intent → JS SDK → webhook)
 *   - PayPal:   redirect-based flow (create PP order → redirect → capture on return)
 *
 * @see App\Http\Controllers\Webhooks\PayMongoController
 */
class PaymentController extends Controller
{
    public function __construct(
        private PayMongoService $payMongo,
        private OrderService $orderService,
        private PaymentManager $paymentManager,
        private MarketplaceCartService $marketplaceCartService,
        private MultiStoreCheckoutService $multiStoreCheckoutService,
        private PayPalService $payPalService
    ) {}

    // ── PayMongo ────────────────────────────────────────────────────────

    /**
     * Create a PayMongo PaymentIntent for the given order.
     */
    public function intent(Request $request, Order $order): JsonResponse
    {
        $this->authorize('createIntent', $order);

        if ($order->status !== OrderStatus::Pending->value) {
            throw ValidationException::withMessages([
                'status' => 'A payment intent can only be created for a Pending order.',
            ]);
        }

        if ($order->payment_intent_id) {
            return response()->json([
                'payment_intent_id' => $order->payment_intent_id,
                'client_key' => $order->payment_client_key,
                'order_id' => $order->id,
            ]);
        }

        $description = "Order #{$order->id} — ".($order->store?->name ?? 'Negosyo Hub');

        $intent = $this->payMongo->createPaymentIntent(
            $order->total->value,
            $description
        );

        $order->update([
            'payment_intent_id' => $intent['id'],
            'payment_method' => OrderPaymentMethod::PayMongo->value,
            'payment_status' => OrderPaymentStatus::Unpaid->value,
            'payment_client_key' => $intent['client_key'],
        ]);

        return response()->json([
            'payment_intent_id' => $intent['id'],
            'client_key' => $intent['client_key'],
            'order_id' => $order->id,
        ]);
    }

    // ── PayPal ──────────────────────────────────────────────────────────

    /**
     * POST /api/v1/paypal/create-order
     *
     * Create a PayPal order from the current cart session.
     * Returns the PayPal approval URL for customer redirect.
     */
    public function paypalCreateOrder(Request $request): JsonResponse
    {
        $this->ensurePayPalCheckoutIsEnabled();

        $cart = CartSession::current(calculate: true);

        if (! $cart || $cart->lines->isEmpty()) {
            return response()->json([
                'message' => 'Your cart is empty. Please add items before placing an order.',
            ], 422);
        }

        if ($this->marketplaceCartService->hasMultipleStores($cart)) {
            $result = $this->paymentManager->initiate(
                OrderPaymentMethod::PayPal->value,
                $cart,
                $this->marketplaceCartService->groupCartByStore($cart)->first()['store']
            );

            return response()->json([
                'paypal_order_id' => $result->externalReference,
                'approve_url' => $result->redirectUrl,
            ]);
        }

        $store = $this->orderService->resolveStoreFromCart($cart);
        $result = $this->paymentManager->initiate(OrderPaymentMethod::PayPal->value, $cart, $store);

        return response()->json([
            'paypal_order_id' => $result->externalReference,
            'approve_url' => $result->redirectUrl,
        ]);
    }

    /**
     * POST /api/v1/paypal/capture-order
     *
     * Capture a PayPal order after the customer approves it.
     * Creates the Lunar order, applies commission, and clears the cart.
     */
    public function paypalCaptureOrder(Request $request): JsonResponse
    {
        $this->ensurePayPalCheckoutIsEnabled();

        $validated = $request->validate([
            'paypal_order_id' => ['required', 'string', 'max:100'],
            'store_id' => ['nullable', 'integer', 'exists:stores,id'],
        ]);

        $cart = CartSession::current();

        if (! $cart) {
            throw ValidationException::withMessages([
                'cart' => ['Cart session expired. Please try again.'],
            ]);
        }

        if ($this->marketplaceCartService->hasMultipleStores($cart)) {
            $paypalOrder = $this->payPalService->getOrder($validated['paypal_order_id']);
            $status = $paypalOrder['status'] ?? null;

            if (! in_array($status, ['APPROVED', 'COMPLETED'], true)) {
                throw ValidationException::withMessages([
                    'paypal_order_id' => 'PayPal order has not been approved by the customer.',
                ]);
            }

            if ($status === 'APPROVED') {
                $paypalOrder = $this->payPalService->captureOrder($validated['paypal_order_id']);
            }

            if (($paypalOrder['status'] ?? null) !== 'COMPLETED') {
                throw ValidationException::withMessages([
                    'paypal_order_id' => 'PayPal payment capture failed. Please try again.',
                ]);
            }

            $this->assertCapturedAmountMatchesMarketplaceCheckout(
                $paypalOrder,
                $cart->calculate()->currency->code,
                $cart->calculate()->total->value,
                (int) (($cart->meta['applied_coupon']['discount_amount'] ?? 0)),
            );

            $captureId = $paypalOrder['purchase_units'][0]['payments']['captures'][0]['id'] ?? $validated['paypal_order_id'];
            $result = $this->multiStoreCheckoutService->completePayPal($cart->calculate(), $validated['paypal_order_id'], $captureId);
            CartSession::manager()->clear();

            return response()->json([
                'message' => 'Payment captured and orders placed successfully.',
                'order_id' => $result['first_order_id'],
                'order_ids' => $result['order_ids'],
                'orders' => $result['orders'],
                'checkout_group_id' => $result['checkout_group_id'],
            ], 201);
        }

        $store = Store::query()->findOrFail($validated['store_id']);
        $result = $this->paymentManager->complete(OrderPaymentMethod::PayPal->value, [
            'paypal_order_id' => $validated['paypal_order_id'],
            'store' => $store,
        ]);
        $order = $result->order;

        return response()->json([
            'message' => $result->message,
            'order_id' => $order?->id,
            'order' => $order,
            'summary' => $order ? $this->orderService->summarize($order) : null,
        ], 201);
    }

    private function ensurePayPalCheckoutIsEnabled(): void
    {
        if (! GlobalSeoSetting::current()->paypal_checkout_enabled) {
            throw ValidationException::withMessages([
                'payment_method' => 'PayPal checkout is temporarily unavailable.',
            ]);
        }
    }

    private function assertCapturedAmountMatchesMarketplaceCheckout(
        array $paypalOrder,
        string $expectedCurrency,
        int $cartTotalInCents,
        int $discountInCents = 0
    ): void {
        $expectedAmountInCents = max(0, $cartTotalInCents - $discountInCents);
        $purchaseUnit = $paypalOrder['purchase_units'][0] ?? [];
        $capturedAmount = $purchaseUnit['payments']['captures'][0]['amount']
            ?? $purchaseUnit['amount']
            ?? null;

        $capturedCurrency = strtoupper((string) ($capturedAmount['currency_code'] ?? ''));
        $capturedValue = $this->normalizeAmountToCents($capturedAmount['value'] ?? null);

        if (
            $capturedValue === null
            || $capturedCurrency !== strtoupper($expectedCurrency)
            || $capturedValue !== $expectedAmountInCents
        ) {
            throw ValidationException::withMessages([
                'paypal_order_id' => 'Captured PayPal amount does not match the server-calculated checkout total.',
            ]);
        }
    }

    private function normalizeAmountToCents(mixed $value): ?int
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        return (int) round(((float) $value) * 100);
    }
}
