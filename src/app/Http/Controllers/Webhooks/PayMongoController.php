<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\PayMongoService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Handles inbound PayMongo webhook events.
 *
 * This controller is intentionally outside the auth:sanctum middleware group.
 * Security is enforced by HMAC-SHA256 signature verification on every request.
 *
 * Step 3 of the two-step payment flow:
 *   1. Frontend POSTs to /api/v1/orders/{order}/intent
 *   2. Frontend collects payment via PayMongo JS SDK
 *   3. PayMongo fires this webhook → payment status is updated server-side
 *
 * @see App\Http\Controllers\Api\V1\PaymentController
 * @see https://developers.paymongo.com/docs/webhook-signature-verification
 */
class PayMongoController extends Controller
{
    public function __construct(
        private PayMongoService $payMongo,
        private OrderService $orderService
    ) {}

    /**
     * Handle an inbound PayMongo webhook.
     *
     * Always returns 200 to acknowledge receipt — PayMongo will retry on non-2xx.
     * Always returns 401 on signature failure so replay attacks are visible in logs.
     */
    public function handle(Request $request): Response
    {
        $rawPayload = $request->getContent();
        $signatureHeader = $request->header('Paymongo-Signature', '');

        if (! $this->payMongo->verifyWebhookSignature($rawPayload, $signatureHeader)) {
            Log::warning('PayMongo webhook: invalid signature', [
                'ip' => $request->ip(),
                'signature' => $signatureHeader,
            ]);

            return response('Unauthorized', 401);
        }

        $event = $this->payMongo->parseWebhookEvent($request->json()->all());

        Log::info('PayMongo webhook received', $event);

        match ($event['event']) {
            'payment.paid' => $this->handlePaymentPaid($event['payment_intent_id']),
            'payment.failed' => $this->handlePaymentFailed($event['payment_intent_id']),
            default => null, // Ignore unhandled events.
        };

        return response('OK', 200);
    }

    /**
     * Mark the order as paid after a successful payment.
     */
    private function handlePaymentPaid(?string $paymentIntentId): void
    {
        if (! $paymentIntentId) {
            return;
        }

        $order = Order::query()
            ->where('payment_intent_id', $paymentIntentId)
            ->first();

        if (! $order) {
            Log::warning('PayMongo webhook: order not found for payment intent', [
                'payment_intent_id' => $paymentIntentId,
            ]);

            return;
        }

        try {
            $this->orderService->markPaymentPaid($order);
        } catch (\Throwable $e) {
            Log::error('PayMongo webhook: failed to mark order paid', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Cancel the order after a failed payment.
     */
    private function handlePaymentFailed(?string $paymentIntentId): void
    {
        if (! $paymentIntentId) {
            return;
        }

        $order = Order::query()
            ->where('payment_intent_id', $paymentIntentId)
            ->first();

        if (! $order) {
            Log::warning('PayMongo webhook: order not found for failed payment intent', [
                'payment_intent_id' => $paymentIntentId,
            ]);

            return;
        }

        try {
            $this->orderService->markPaymentFailed($order);
        } catch (\Throwable $e) {
            Log::error('PayMongo webhook: failed to cancel unpaid order', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
