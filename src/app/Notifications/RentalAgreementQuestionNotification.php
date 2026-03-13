<?php

namespace App\Notifications;

use App\Models\RentalAgreement;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class RentalAgreementQuestionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public RentalAgreement $agreement,
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
        $property = $this->agreement->property?->title ?? 'a property';

        return FilamentNotification::make()
            ->title('Tenant Question on Rental Agreement')
            ->body("{$this->agreement->tenant_name} has a question regarding the agreement for {$property}.")
            ->icon('heroicon-o-chat-bubble-left-right')
            ->iconColor('warning')
            ->getDatabaseMessage();
    }
}
