<?php

namespace App\Policies;

use App\Models\MovingBooking;
use App\Models\User;
use App\MovingBookingStatus;

class MovingBookingPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, MovingBooking $booking): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Store owner can view their own bookings
        if ($user->isStoreOwner() && (int) $booking->store?->user_id === $user->id) {
            return true;
        }

        // Customers can view their own bookings
        return (int) $booking->customer_user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isCustomer();
    }

    /**
     * Only the moving company owner may update booking status.
     */
    public function updateStatus(User $user, MovingBooking $booking): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isStoreOwner()
            && (int) $booking->store?->user_id === $user->id
            && ! in_array($booking->status, [
                MovingBookingStatus::Completed,
                MovingBookingStatus::Cancelled,
            ], true);
    }

    /**
     * Customers can cancel their own pending bookings.
     */
    public function cancel(User $user, MovingBooking $booking): bool
    {
        return (int) $booking->customer_user_id === $user->id
            && $booking->status === MovingBookingStatus::Pending;
    }
}
