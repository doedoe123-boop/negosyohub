<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Store;
use App\OrderPaymentMethod;
use App\OrderPaymentStatus;
use App\OrderStatus;
use App\Services\OrderService;
use App\Services\PaymentManager;
use App\Services\PayMongoService;
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
        private PaymentManager $paymentManager
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
        $cart = CartSession::current(calculate: true);

        if (! $cart || $cart->lines->isEmpty()) {
            return response()->json([
                'message' => 'Your cart is empty. Please add items before placing an order.',
            ], 422);
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
        $validated = $request->validate([
            'paypal_order_id' => ['required', 'string', 'max:100'],
            'store_id' => ['required', 'integer', 'exists:stores,id'],
        ]);

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
}
