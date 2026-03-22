<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use App\OrderStatus;

/**
 * @see /skills/order-processing.md
 */
class OrderPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Order $order): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isStoreOwner() && (int) $order->store?->user_id === $user->id) {
            return true;
        }

        return (int) $order->user_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isCustomer();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Order $order): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isStoreOwner() && (int) $order->store?->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Order $order): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Order $order): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Order $order): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can cancel the order.
     *
     * - Admins may cancel any active order.
     * - Store owners may cancel any active order belonging to their store.
     * - Customers may only cancel their own order while it is still Pending
     *   (i.e. before the store owner has begun work on it).
     */
    public function cancel(User $user, Order $order): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isStoreOwner() && (int) $order->store?->user_id === $user->id) {
            return true;
        }

        // Customers may only cancel while the order is still Pending.
        return (int) $order->user_id === $user->id
            && $order->status === OrderStatus::Pending->value;
    }

    /**
     * Determine whether the customer can create a payment intent for the order.
     *
     * Only the order's own customer may initiate payment.
     */
    public function createIntent(User $user, Order $order): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return (int) $order->user_id === $user->id;
    }

    /**
     * Determine whether the user can confirm the order (store owner / admin).
     */
    public function confirm(User $user, Order $order): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isStoreOwner() && (int) $order->store?->user_id === $user->id;
    }

    /**
     * Determine whether the user can mark the order as preparing (store owner / admin).
     */
    public function prepare(User $user, Order $order): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isStoreOwner() && (int) $order->store?->user_id === $user->id;
    }

    /**
     * Determine whether the user can mark the order as ready (store owner / admin).
     */
    public function markReady(User $user, Order $order): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isStoreOwner() && (int) $order->store?->user_id === $user->id;
    }

    /**
     * Determine whether the user can mark the order as delivered (store owner / admin).
     */
    public function deliver(User $user, Order $order): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isStoreOwner() && (int) $order->store?->user_id === $user->id;
    }

    /**
     * Determine whether the user can mark the order as paid.
     */
    public function markPaid(User $user, Order $order): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isStoreOwner() && (int) $order->store?->user_id === $user->id;
    }
}
