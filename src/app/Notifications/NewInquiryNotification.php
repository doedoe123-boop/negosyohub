<?php

namespace App\Notifications;

use App\Models\PropertyInquiry;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * In-app (database) notification sent to the agent when a new property inquiry
 * is submitted.
 *
 * This is the real estate equivalent of OrderPlacedNotification.
 * Powers the Filament bell icon in the realty panel.
 */
class NewInquiryNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public PropertyInquiry $inquiry,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $property = $this->inquiry->property?->title ?? 'a property';

        return FilamentNotification::make()
            ->title('New Property Inquiry')
            ->body("{$this->inquiry->name} inquired about {$property}")
            ->icon('heroicon-o-chat-bubble-left-right')
            ->iconColor('info')
            ->getDatabaseMessage();
    }
}
