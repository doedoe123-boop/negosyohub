<?php

namespace App;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Preparing = 'preparing';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    /**
     * Human-readable label for display.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Confirmed => 'Confirmed',
            self::Preparing => 'Preparing',
            self::Shipped => 'Shipped',
            self::Delivered => 'Delivered',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * Filament-compatible badge color.
     */
    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Confirmed => 'info',
            self::Preparing => 'primary',
            self::Shipped => 'success',
            self::Delivered => 'success',
            self::Cancelled => 'danger',
        };
    }

    /**
     * Statuses that indicate an active (unfulfilled) order.
     *
     * Only these statuses permit the order to be cancelled or progressed.
     * Terminal statuses (Delivered, Cancelled, PaymentFailed, Refunded) are excluded.
     *
     * @return list<self>
     */
    public static function active(): array
    {
        return [self::Pending, self::Confirmed, self::Preparing, self::Shipped];
    }

    /**
     * Explicit state-machine transition matrix.
     *
     * Returns true when this status is a valid source state for transitioning
     * to $next.  Prevents invalid hops regardless of who triggers them.
     */
    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::Pending => in_array($next, [
                self::Confirmed,
                self::Cancelled,
            ], true),
            self::Confirmed => in_array($next, [
                self::Preparing,
                self::Cancelled,
            ], true),
            self::Preparing => in_array($next, [
                self::Shipped,
                self::Cancelled,
            ], true),
            self::Shipped => in_array($next, [
                self::Delivered,
                self::Cancelled,
            ], true),
            self::Delivered, self::Cancelled => false,
        };
    }
}
