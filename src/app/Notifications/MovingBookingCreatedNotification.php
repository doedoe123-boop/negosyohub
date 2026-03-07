<?php

namespace App\Notifications;

use App\Models\MovingBooking;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class MovingBookingCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public MovingBooking $booking) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title('New Moving Booking')
            ->body("Booking #{$this->booking->id} from {$this->booking->contact_name} on ".$this->booking->scheduled_at->format('M j, Y g:i A').'.')
            ->success()
            ->getDatabaseMessage();
    }
}
