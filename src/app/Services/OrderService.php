<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Store;
use App\OrderStatus;
use Illuminate\Support\Facades\DB;
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
        private CommissionService $commissionService
    ) {}

    /**
     * Validate and create an order from a Lunar cart for a specific store.
     *
     * @throws ValidationException
     */
    public function createFromCart(Cart $cart, Store $store): Order
    {
        $this->validateCart($cart);
        $this->validateStore($store);
        $this->validateCartBelongsToStore($cart, $store);

        return DB::transaction(function () use ($cart, $store): Order {
            $cart = $cart->calculate();

            /** @var Order $order */
            $order = $cart->createOrder();

            $order->update([
                'store_id' => $store->id,
                'status' => OrderStatus::Pending->value,
            ]);

            // See /skills/commission-calculation.md
            $this->commissionService->applyToOrder($order);

            return $order->refresh();
        });
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
     * @throws ValidationException
     */
    public function validateCartBelongsToStore(Cart $cart, Store $store): void
    {
        $foreignLines = $cart->lines->filter(
            fn ($line) => $line->purchasable?->product?->attribute_data?->get('store_id') !== $store->id
        );

        if ($foreignLines->isNotEmpty()) {
            throw ValidationException::withMessages([
                'cart' => 'All cart items must belong to the same store.',
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
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function listForUser(\App\Models\User $user, int $perPage = 15)
    {
        $query = Order::query()->with('store');

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

        return $order->refresh();
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

        return $order->refresh();
    }

    /**
     * Mark a preparing order as ready for pickup/delivery.
     *
     * @throws ValidationException
     */
    public function markReady(Order $order): Order
    {
        $this->assertStatus($order, OrderStatus::Preparing);
        $order->update(['status' => OrderStatus::Ready->value]);

        return $order->refresh();
    }

    /**
     * Mark a ready order as delivered.
     *
     * @throws ValidationException
     */
    public function markDelivered(Order $order): Order
    {
        $this->assertStatus($order, OrderStatus::Ready);
        $order->update(['status' => OrderStatus::Delivered->value]);

        return $order->refresh();
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

        return $order->refresh();
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
