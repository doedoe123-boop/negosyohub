<?php

namespace App\Notifications;

use App\Models\RentalAgreement;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * In-app (database) notification sent to the landlord when a rental agreement is created.
 *
 * Powers the Filament bell icon in the Realty panel.
 */
class RentalConfirmedLandlordNotification extends Notification implements ShouldQueue
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
            ->title('Rental Agreement Confirmed')
            ->body("{$this->agreement->tenant_name} has been registered as a tenant for {$property}.")
            ->icon('heroicon-o-home')
            ->iconColor('success')
            ->getDatabaseMessage();
    }
}
