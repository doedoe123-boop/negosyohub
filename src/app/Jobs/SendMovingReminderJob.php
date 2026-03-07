<?php

namespace App\Jobs;

use App\Models\MovingBooking;
use App\MovingBookingStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendMovingReminderJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public MovingBooking $booking) {}

    public function handle(): void
    {
        if (! in_array($this->booking->status, [
            MovingBookingStatus::Confirmed,
            MovingBookingStatus::Pending,
        ], true)) {
            return;
        }

        $customer = $this->booking->customer;

        if (! $customer) {
            return;
        }

        Mail::to($customer)->send(new \App\Mail\MovingReminderMail($this->booking));
    }
}
