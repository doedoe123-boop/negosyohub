<?php

use App\Jobs\ExpireAnnouncementsJob;
use App\Jobs\PurgeExpiredDocumentsJob;
use App\Jobs\SendMovingReminderJob;
use App\Models\MovingBooking;
use App\MovingBookingStatus;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Data lifecycle — daily cleanup of expired documents and soft-deleted records
Schedule::job(new PurgeExpiredDocumentsJob)->daily()->at('03:00');

// Deactivate announcements past their expiry date
Schedule::job(new ExpireAnnouncementsJob)->hourly();

// Send 24-hour-ahead reminders to customers for upcoming moves
Schedule::call(function () {
    $tomorrow = now()->addDay();

    MovingBooking::query()
        ->whereIn('status', [MovingBookingStatus::Confirmed->value, MovingBookingStatus::Pending->value])
        ->whereBetween('scheduled_at', [$tomorrow->copy()->startOfHour(), $tomorrow->copy()->endOfHour()])
        ->each(fn (MovingBooking $booking) => SendMovingReminderJob::dispatch($booking));
})->hourly()->name('moving-reminders');
