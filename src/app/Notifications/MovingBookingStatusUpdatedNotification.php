<?php

namespace App\Notifications;

use App\Models\MovingBooking;
use App\MovingBookingStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MovingBookingStatusUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public MovingBooking $booking,
        public MovingBookingStatus $newStatus,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $label = $this->newStatus->label();

        $message = (new MailMessage)
            ->subject("Your Moving Booking #{$this->booking->id} — {$label}")
            ->greeting("Hi {$this->booking->contact_name},")
            ->line("Your moving booking scheduled for **{$this->booking->scheduled_at->format('F j, Y \a\t g:i A')}** has been updated to: **{$label}**.");

        if ($this->newStatus === MovingBookingStatus::Confirmed) {
            $message = $message
                ->line('Your moving company has confirmed your booking. Please be ready at the pickup address on the scheduled date.')
                ->action('View Booking', url("/moving/{$this->booking->id}"));
        } elseif ($this->newStatus === MovingBookingStatus::Completed) {
            $message = $message
                ->line('Your move is complete! We hope everything went smoothly.')
                ->action('Leave a Review', url("/moving/{$this->booking->id}#review"));
        } elseif ($this->newStatus === MovingBookingStatus::Cancelled) {
            $message = $message
                ->line('Your booking has been cancelled. Please contact the moving company or our support for assistance.');
        }

        return $message->line('Thank you for using Negosyo Hub!');
    }
}
