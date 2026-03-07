<?php

namespace App\Mail;

use App\Models\MovingBooking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MovingReminderMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public MovingBooking $booking) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Reminder: Your Move Tomorrow — Booking #{$this->booking->id}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.moving.reminder',
        );
    }
}
