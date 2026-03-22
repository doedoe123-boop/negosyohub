<?php

namespace App\Services;

use App\Mail\NewOrderReceived;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use App\Notifications\OrderPlacedNotification;
use App\Notifications\OrderStatusUpdated;
use App\OrderPaymentMethod;
use App\OrderPaymentStatus;
use App\OrderStatus;
use App\Services\Webhooks\WebhookEventDispatcher;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Lunar\Models\Cart;

/**
 * Handles order creation, validation, and lifecycle for the marketplace.
 *
 * Validates cart integrity (non-empty, single-store) and store eligibility
 * before delegating to Lunar for order creation and applying commission.
 *
 * @see /skills/order-processing.md
 * @see /agent/order-agent.md
 */
class OrderService
{
    public function __construct(
        private CommissionService $commissionService,
        private CheckoutDiscountService $checkoutDiscountService,
        private WebhookEventDispatcher $webhookEventDispatcher
    ) {}

    /**
     * Validate and create an order from a Lunar cart for a specific store.
     *
     * Wrapped in a distributed cache lock to prevent concurrent double-submissions
     * from the same user (double-click, network retry, duplicate tab).
     *
     * @throws ValidationException
     */
    public function createFromCart(Cart $cart, Store $store, array $attributes = []): Order
    {
        $this->validateCart($cart);
        $this->validateStore($store);
        $this->validateCartBelongsToStore($cart, $store);

        // #5 — Distributed lock: prevents concurrent duplicate order creation.
        // If the same user submits twice within 15 s the second request gets a 409.
        $lock = Cache::lock("order-create:{$cart->customer_id}", 15);

        if (! $lock->get()) {
            abort(409, 'Your previous order is still being processed. Please wait a moment and try again.');
        }

        try {
            $order = DB::transaction(function () use ($cart, $store, $attributes): Order {
                $cart = $cart->calculate();

                /** @var Order $order */
                $order = $cart->createOrder();

                $order->update([
                    'store_id' => $store->id,
                    'status' => OrderStatus::Pending->value,
                    'placed_at' => now(),
                    ...$attributes,
                ]);

                $order = $this->checkoutDiscountService->applyToOrder($order, $cart, $store);

                // See /skills/commission-calculation.md
                $this->commissionService->applyToOrder($order);

                return $order->refresh();
            });
        } finally {
            $lock->release();
        }

        $this->notifyStoreOwner($order);
        $this->webhookEventDispatcher->dispatchForOrder($order, 'order.created');

        return $order;
    }

    /**
     * Resolve the store represented by the cart contents.
     *
     * @throws ValidationException
     */
    public function resolveStoreFromCart(Cart $cart): Store
    {
        $storeId = (int) ($cart->meta['store_id'] ?? 0);

        if (! $storeId) {
            $storeId = (int) ($cart->lines->first()?->purchasable?->product?->attribute_data?->get('store_id')?->getValue() ?? 0);
        }

        if (! $storeId) {
            throw ValidationException::withMessages([
                'cart' => 'Unable to determine which store should fulfill this order.',
            ]);
        }

        return Store::query()->findOrFail($storeId);
    }

    /**
     * Ensure the cart is not empty.
     *
     * @throws ValidationException
     */
    public function validateCart(Cart $cart): void
    {
        if ($cart->lines->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => 'Cart is empty. Please add items before placing an order.',
            ]);
        }
    }

    /**
     * Ensure the store is approved and eligible to receive orders.
     *
     * @throws ValidationException
     */
    public function validateStore(Store $store): void
    {
        if (! $store->isApproved()) {
            throw ValidationException::withMessages([
                'store_id' => 'This store is not currently accepting orders.',
            ]);
        }
    }

    /**
     * Ensure all cart line items belong to the specified store.
     *
     * Cart items must come from a single store to maintain tenant isolation.
     *
     * Uses integer casting when comparing the Lunar attribute_data store_id
     * (stored as a string inside the JSONB attribute bag) against the store's
     * integer primary key.  Without the cast, strict !== comparison would
     * treat "1" !== 1 as true and incorrectly flag every item as foreign.
     *
     * @throws ValidationException
     */
    public function validateCartBelongsToStore(Cart $cart, Store $store): void
    {
        $foreignLines = $cart->lines->filter(function ($line) use ($store): bool {
            $rawValue = $line->purchasable?->product?->attribute_data?->get('store_id')?->getValue();

            // Treat missing attribute as a foreign item (null → 0 != any real store id)
            $lineStoreId = (int) ($rawValue ?? 0);

            return $lineStoreId !== $store->id;
        });

        if ($foreignLines->isNotEmpty()) {
            throw ValidationException::withMessages([
                'cart' => 'All cart items must belong to the same store.',
            ]);
        }
    }

    /**
     * Queue email + in-app (database) notification to the store owner on new order.
     *
     * Failures are logged but never bubble up — a failed notification must never
     * roll back or block a successfully placed order.
     */
    private function notifyStoreOwner(Order $order): void
    {
        $owner = $order->store?->owner;

        if (! $owner) {
            return;
        }

        try {
            // Email
            Mail::to($owner->email)->queue(new NewOrderReceived($order));
            // In-app bell
            $owner->notify(new OrderPlacedNotification($order));
        } catch (\Throwable $e) {
            Log::warning('Failed to queue new-order notification', [
                'order_id' => $order->id,
                'store_id' => $order->store_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Queue email + in-app (database) notification to the customer on status change.
     *
     * Failures are logged but never bubble up.
     */
    private function notifyCustomer(Order $order): void
    {
        $customer = $order->user;

        if (! $customer) {
            return;
        }

        try {
            $customer->notify(new OrderStatusUpdated($order));
        } catch (\Throwable $e) {
            Log::warning('Failed to queue order-status notification', [
                'order_id' => $order->id,
                'status' => $order->status,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Generate an order summary array.
     *
     * @return array{order_id: int, store: string, total: int, commission: int, store_earning: int, platform_earning: int, status: string}
     */
    public function summarize(Order $order): array
    {
        return [
            'order_id' => $order->id,
            'store' => $order->store?->name ?? 'Unknown',
            'total' => $order->total->value,
            'commission' => $order->commission_amount->value,
            'store_earning' => $order->store_earning->value,
            'platform_earning' => $order->platform_earning->value,
            'status' => $order->status,
        ];
    }

    /**
     * Return a paginated list of orders scoped to the given user's role.
     *
     * - Admins see all orders.
     * - Store owners see only their store's orders.
     * - Customers see only their own orders.
     *
     * @return LengthAwarePaginator
     */
    public function listForUser(User $user, int $perPage = 15)
    {
        $query = Order::query()->with(['store', 'latestShipment']);

        if ($user->isStoreOwner()) {
            $query->where('store_id', $user->store?->id);
        } elseif ($user->isCustomer()) {
            $query->where('user_id', $user->id);
        }

        return $query->latest()->paginate($perPage);
    }

    // ── Status Transitions ─────────────────────────────────────────────

    /**
     * Confirm a pending order.
     *
     * @throws ValidationException
     */
    public function confirm(Order $order): Order
    {
        $this->assertStatus($order, OrderStatus::Pending);
        $order->update(['status' => OrderStatus::Confirmed->value]);
        $order->refresh();

        $this->notifyCustomer($order);
        $this->webhookEventDispatcher->dispatchForOrder($order, 'order.updated');

        return $order;
    }

    /**
     * Mark a confirmed order as preparing.
     *
     * @throws ValidationException
     */
    public function markPreparing(Order $order): Order
    {
        $this->assertStatus($order, OrderStatus::Confirmed);
        $order->update(['status' => OrderStatus::Preparing->value]);
        $order->refresh();

        $this->notifyCustomer($order);
        $this->webhookEventDispatcher->dispatchForOrder($order, 'order.updated');

        return $order;
    }

    /**
     * Mark a preparing order as ready for pickup/delivery.
     *
     * @throws ValidationException
     */
    public function markShipped(Order $order): Order
    {
        $this->assertStatus($order, OrderStatus::Preparing);
        $order->update(['status' => OrderStatus::Shipped->value]);
        $order->refresh();

        $this->notifyCustomer($order);
        $this->webhookEventDispatcher->dispatchForOrder($order, 'order.updated');

        return $order;
    }

    /**
     * Backward-compatible alias for older callers.
     *
     * @throws ValidationException
     */
    public function markReady(Order $order): Order
    {
        return $this->markShipped($order);
    }

    /**
     * Mark a ready order as delivered.
     *
     * @throws ValidationException
     */
    public function markDelivered(Order $order): Order
    {
        if (! in_array($order->status, [OrderStatus::Shipped->value], true)) {
            throw ValidationException::withMessages([
                'status' => 'Order must be Shipped to perform this action.',
            ]);
        }
        $order->update(['status' => OrderStatus::Delivered->value]);
        $order->refresh();

        $this->notifyCustomer($order);
        $this->webhookEventDispatcher->dispatchForOrder($order, 'order.delivered');

        return $order;
    }

    /**
     * Cancel an order (allowed from any active status).
     *
     * @throws ValidationException
     */
    public function cancel(Order $order): Order
    {
        $activeStatuses = array_map(fn (OrderStatus $s) => $s->value, OrderStatus::active());

        if (! in_array($order->status, $activeStatuses, true)) {
            throw ValidationException::withMessages([
                'status' => 'Only active orders can be cancelled.',
            ]);
        }

        $order->update(['status' => OrderStatus::Cancelled->value]);
        $order->refresh();

        $this->notifyCustomer($order);
        $this->webhookEventDispatcher->dispatchForOrder($order, 'order.updated');

        return $order;
    }

    /**
     * Confirm an order after a successful PayMongo payment.
     *
     * Called by the PayMongo webhook controller — never trust the frontend redirect.
     *
     * @throws ValidationException
     */
    public function markPaymentPaid(Order $order): Order
    {
        $order->update([
            'payment_status' => OrderPaymentStatus::Paid->value,
            'paid_at' => now(),
        ]);
        $order->refresh();

        $this->notifyCustomer($order);
        $this->webhookEventDispatcher->dispatchForOrder($order, 'payment.paid');

        return $order;
    }

    /**
     * Mark an order as PaymentFailed after a failed PayMongo payment.
     *
     * Called by the PayMongo webhook controller.
     *
     * @throws ValidationException
     */
    public function markPaymentFailed(Order $order): Order
    {
        $order->update([
            'status' => OrderStatus::Cancelled->value,
            'payment_status' => OrderPaymentStatus::Unpaid->value,
            'cancelled_at' => now(),
        ]);
        $order->refresh();

        $this->notifyCustomer($order);
        $this->webhookEventDispatcher->dispatchForOrder($order, 'payment.failed');

        return $order;
    }

    /**
     * Mark an unpaid COD order as paid.
     *
     * @throws ValidationException
     */
    public function markPaid(Order $order): Order
    {
        if ($order->payment_method !== OrderPaymentMethod::CashOnDelivery) {
            throw ValidationException::withMessages([
                'payment_method' => 'Only Cash on Delivery orders can be marked as paid manually.',
            ]);
        }

        if ($order->payment_status === OrderPaymentStatus::Paid) {
            throw ValidationException::withMessages([
                'payment_status' => 'This order is already marked as paid.',
            ]);
        }

        $order->update([
            'payment_status' => OrderPaymentStatus::Paid->value,
            'paid_at' => now(),
        ]);

        $this->recordPaymentCapture(
            $order->refresh(),
            driver: OrderPaymentMethod::CashOnDelivery->value,
            reference: "cod-paid-{$order->id}-".now()->timestamp,
            status: 'COMPLETED',
        );

        $this->webhookEventDispatcher->dispatchForOrder($order->refresh(), 'payment.paid');

        return $order->refresh();
    }

    public function recordPaymentCapture(Order $order, string $driver, string $reference, string $status = 'COMPLETED'): void
    {
        $order->transactions()->create([
            'success' => true,
            'type' => 'capture',
            'driver' => $driver,
            'amount' => $order->total->value,
            'reference' => $reference,
            'status' => $status,
            'card_type' => $driver,
            'captured_at' => now(),
        ]);
    }

    /**
     * Assert an order is in the expected status before transitioning.
     *
     * @throws ValidationException
     */
    private function assertStatus(Order $order, OrderStatus $expected): void
    {
        if ($order->status !== $expected->value) {
            throw ValidationException::withMessages([
                'status' => "Order must be {$expected->label()} to perform this action.",
            ]);
        }
    }
}
