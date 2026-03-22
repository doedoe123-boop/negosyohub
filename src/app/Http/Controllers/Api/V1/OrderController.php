<?php

namespace App\Http\Controllers\Api\V1;

use App\DeliveryStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ManageShipmentRequest;
use App\Http\Requests\PlaceOrderRequest;
use App\Http\Requests\UpdateShipmentStatusRequest;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\Store;
use App\Services\LogisticsManager;
use App\Services\OrderService;
use App\Services\PaymentManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Lunar\DataTypes\Price;
use Lunar\Facades\CartSession;

/**
 * Thin HTTP handler for customer order operations.
 *
 * Role-scoped querying, validation, and commission logic
 * all live in OrderService.
 *
 * @see /skills/order-processing.md
 */
class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService,
        private PaymentManager $paymentManager,
        private LogisticsManager $logisticsManager
    ) {}

    /**
     * List orders visible to the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $paginator = $this->orderService->listForUser($request->user());

        $paginator->getCollection()->transform(function (Order $order) {
            $data = $order->toArray();
            $data['latest_shipment'] = $order->latestShipment
                ? $this->logisticsManager->shipmentPayload($order->latestShipment)
                : null;

            return $this->formatOrderPrices($order, $data);
        });

        return response()->json($paginator);
    }

    /**
     * Show a single order with its summary.
     */
    public function show(Request $request, Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        $order->load(['store', 'lines', 'addresses', 'shipments', 'latestShipment']);

        // Eager-load product media only for non-shipping lines
        // (shipping lines use ShippingOption which is not an Eloquent model).
        $productLines = $order->lines->filter(
            fn ($line) => $line->type !== 'shipping'
        );
        if ($productLines->isNotEmpty()) {
            $productLines->load('purchasable.product.media');
        }

        $orderData = $order->toArray();
        $orderData = $this->formatOrderPrices($order, $orderData);
        $orderData['shipments'] = $order->shipments
            ->map(fn (Shipment $shipment): array => $this->logisticsManager->shipmentPayload($shipment))
            ->values()
            ->all();
        $orderData['latest_shipment'] = $order->latestShipment
            ? $this->logisticsManager->shipmentPayload($order->latestShipment)
            : null;
        $orderData['lines'] = $order->lines->map(function ($line) {
            $data = $line->toArray();
            $data['thumbnail'] = $line->relationLoaded('purchasable')
                ? ($line->purchasable?->product?->getFirstMediaUrl('images') ?: null)
                : null;
            $data['unit_price'] = self::priceToArray($line->unit_price);
            $data['sub_total'] = self::priceToArray($line->sub_total);
            $data['total'] = self::priceToArray($line->total);
            $data['discount_total'] = self::priceToArray($line->discount_total);
            $data['tax_total'] = self::priceToArray($line->tax_total);

            return $data;
        })->all();

        return response()->json([
            'order' => $orderData,
            'summary' => $this->orderService->summarize($order),
        ]);
    }

    /**
     * Cancel a pending or confirmed order.
     */
    public function cancel(Request $request, Order $order): JsonResponse
    {
        $this->authorize('cancel', $order);

        $order = $this->orderService->cancel($order);

        return response()->json([
            'message' => 'Order cancelled successfully.',
            'order' => $order,
        ]);
    }

    /**
     * Place a new order from the current Lunar cart session.
     *
     * Guard order: validate cart exists (422) before touching the DB.
     * After a successful order the cart is cleared so that a page-refresh,
     * network retry, or double-click cannot create a duplicate order.
     *
     * X-Idempotency-Key: if provided, the 201 response is cached for 24 h.
     * A repeat request with the same key returns the cached response immediately.
     */
    public function store(PlaceOrderRequest $request): JsonResponse
    {
        // #5 — Idempotency: return the cached response for duplicate submissions.
        $idempotencyKey = $request->header('X-Idempotency-Key');
        if ($idempotencyKey) {
            $cacheKey = "idempotency:order:{$request->user()->id}:{$idempotencyKey}";
            if ($cached = Cache::get($cacheKey)) {
                return response()->json($cached, 201);
            }
        }

        // #3 — return a clean 422 instead of a fatal TypeError when no cart exists.
        $cart = CartSession::current();

        if (! $cart) {
            return response()->json([
                'message' => 'Your cart is empty. Please add items before placing an order.',
            ], 422);
        }

        // #10 — store resolution lives in the service; controller only passes
        // the validated primitive so the service owns the full domain flow.
        $store = Store::query()->findOrFail($request->validated('store_id'));
        $paymentMethod = $request->validated('payment_method');
        $result = $this->paymentManager->initiate($paymentMethod, $cart, $store);

        if ($result->requiresRedirect()) {
            return response()->json([
                'message' => $result->message,
                'payment_method' => $result->method->value,
                'approve_url' => $result->redirectUrl,
                'paypal_order_id' => $result->externalReference,
            ]);
        }

        $order = $result->order;

        $responseData = [
            'message' => $result->message,
            'order_id' => $order?->id,
            'order' => $order,
            'summary' => $order ? $this->orderService->summarize($order) : null,
        ];

        // Cache the response against the idempotency key for 24 hours.
        if ($idempotencyKey) {
            Cache::put($cacheKey, $responseData, now()->addHours(24));
        }

        return response()->json($responseData, 201);
    }

    // ── Store-owner order progression ─────────────────────────────────

    /**
     * Confirm a pending order (store owner / admin only).
     */
    public function confirm(Request $request, Order $order): JsonResponse
    {
        $this->authorize('confirm', $order);

        $order = $this->orderService->confirm($order);

        return response()->json([
            'message' => 'Order confirmed.',
            'order' => $order,
            'summary' => $this->orderService->summarize($order),
        ]);
    }

    /**
     * Mark a confirmed order as preparing (store owner / admin only).
     */
    public function prepare(Request $request, Order $order): JsonResponse
    {
        $this->authorize('prepare', $order);

        $order = $this->orderService->markPreparing($order);

        return response()->json([
            'message' => 'Order is now being prepared.',
            'order' => $order,
            'summary' => $this->orderService->summarize($order),
        ]);
    }

    /**
     * Mark an order as ready for pickup / delivery (store owner / admin only).
     */
    public function ship(Request $request, Order $order): JsonResponse
    {
        $this->authorize('deliver', $order);

        $order = $this->orderService->markShipped($order);

        return response()->json([
            'message' => 'Order has been marked as shipped.',
            'order' => $order,
            'summary' => $this->orderService->summarize($order),
        ]);
    }

    public function markPaid(Request $request, Order $order): JsonResponse
    {
        $this->authorize('markPaid', $order);

        $order = $this->orderService->markPaid($order);

        return response()->json([
            'message' => 'Order payment marked as paid.',
            'order' => $order,
            'summary' => $this->orderService->summarize($order),
        ]);
    }

    public function upsertShipment(ManageShipmentRequest $request, Order $order): JsonResponse
    {
        $this->authorize('update', $order);

        $shipment = $this->logisticsManager->upsertShipment($order->loadMissing('store', 'addresses'), $request->validated());

        return response()->json([
            'message' => 'Shipment saved.',
            'shipment' => $this->logisticsManager->shipmentPayload($shipment),
        ]);
    }

    public function updateShipmentStatus(UpdateShipmentStatusRequest $request, Order $order, Shipment $shipment): JsonResponse
    {
        $this->authorize('update', $order);
        abort_unless($shipment->order_id === $order->id, 404);

        $updatedShipment = $this->logisticsManager->updateStatus(
            $shipment,
            DeliveryStatus::from($request->validated('delivery_status')),
            $request->validated()
        );

        return response()->json([
            'message' => 'Shipment status updated.',
            'shipment' => $this->logisticsManager->shipmentPayload($updatedShipment),
        ]);
    }

    /**
     * Mark a ready order as delivered (store owner / admin only).
     */
    public function deliver(Request $request, Order $order): JsonResponse
    {
        $this->authorize('deliver', $order);

        $order = $this->orderService->markDelivered($order);

        return response()->json([
            'message' => 'Order marked as delivered.',
            'order' => $order,
            'summary' => $this->orderService->summarize($order),
        ]);
    }

    /**
     * Convert a Lunar Price DataType into a frontend-friendly array.
     *
     * @return array{value: int, formatted: string}|null
     */
    private static function priceToArray(mixed $price): ?array
    {
        if (! $price instanceof Price) {
            return null;
        }

        return [
            'value' => $price->value,
            'formatted' => '₱'.number_format($price->decimal, 2),
        ];
    }

    /**
     * Replace Lunar Price cast fields with {value, formatted} arrays.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function formatOrderPrices(Order $order, array $data): array
    {
        $data['sub_total'] = self::priceToArray($order->sub_total);
        $data['discount_total'] = self::priceToArray($order->discount_total);
        $data['shipping_total'] = self::priceToArray($order->shipping_total);
        $data['tax_total'] = self::priceToArray($order->tax_total);
        $data['total'] = self::priceToArray($order->total);
        $data['payment_method_label'] = $order->payment_method?->label();
        $data['payment_status_label'] = $order->payment_status?->label();
        $data['payment_status_helper'] = $order->payment_status?->helperText();

        return $data;
    }
}
