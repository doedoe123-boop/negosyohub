<?php

namespace App;

/**
 * Lifecycle states for a moving service booking.
 */
enum MovingBookingStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending Confirmation',
            self::Confirmed => 'Confirmed',
            self::InProgress => 'In Progress',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function canTransitionTo(self $target): bool
    {
        if ($this === $target) {
            return true;
        }

        return match ($this) {
            self::Pending => in_array($target, [self::Confirmed, self::Cancelled], true),
            self::Confirmed => in_array($target, [self::InProgress, self::Cancelled], true),
            self::InProgress => $target === self::Completed,
            self::Completed, self::Cancelled => false,
        };
    }

    /**
     * @return list<self>
     */
    public function nextStatuses(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $candidate): bool => $this->canTransitionTo($candidate) && $candidate !== $this
        ));
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Confirmed => 'info',
            self::InProgress => 'primary',
            self::Completed => 'success',
            self::Cancelled => 'danger',
        };
    }
}
