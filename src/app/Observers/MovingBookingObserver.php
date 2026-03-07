<?php

namespace App\Observers;

use App\Models\MovingBooking;
use App\Notifications\MovingBookingCreatedNotification;
use App\Notifications\MovingBookingStatusUpdatedNotification;
use Illuminate\Support\Facades\Log;

class MovingBookingObserver
{
    /**
     * Handle the MovingBooking "created" event.
     *
     * Notify the moving company owner (store owner) via in-app bell.
     */
    public function created(MovingBooking $movingBooking): void
    {
        try {
            $owner = $movingBooking->store->owner ?? null;

            $owner?->notify(new MovingBookingCreatedNotification($movingBooking));
        } catch (\Throwable $e) {
            Log::warning('MovingBookingObserver::created notification failed', [
                'booking_id' => $movingBooking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the MovingBooking "updated" event.
     *
     * When status changes, notify the customer by email.
     */
    public function updated(MovingBooking $movingBooking): void
    {
        if (! $movingBooking->wasChanged('status')) {
            return;
        }

        try {
            $customer = $movingBooking->customer;
            $newStatus = $movingBooking->status;

            $customer?->notify(new MovingBookingStatusUpdatedNotification($movingBooking, $newStatus));
        } catch (\Throwable $e) {
            Log::warning('MovingBookingObserver::updated notification failed', [
                'booking_id' => $movingBooking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
