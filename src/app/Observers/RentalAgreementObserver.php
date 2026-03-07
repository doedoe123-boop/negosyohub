<?php

namespace App\Observers;

use App\Models\RentalAgreement;
use App\Notifications\RentalConfirmedLandlordNotification;
use App\Notifications\RentalConfirmedTenantNotification;
use App\PropertyStatus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class RentalAgreementObserver
{
    /**
     * Handle the RentalAgreement "created" event.
     *
     * Marks the property as Rented and dispatches notifications to both the
     * tenant (email) and the landlord (in-app bell).
     */
    public function created(RentalAgreement $agreement): void
    {
        // Mark the property as rented so it disappears from active listings
        try {
            $agreement->property()->update(['status' => PropertyStatus::Rented]);
        } catch (\Throwable $e) {
            Log::warning('Failed to mark property as rented', [
                'agreement_id' => $agreement->id,
                'property_id' => $agreement->property_id,
                'error' => $e->getMessage(),
            ]);
        }

        // Notify the tenant via email with a CTA to browse moving services
        try {
            Notification::route('mail', $agreement->tenant_email)
                ->notify(new RentalConfirmedTenantNotification($agreement));
        } catch (\Throwable $e) {
            Log::warning('Failed to send rental-confirmed email to tenant', [
                'agreement_id' => $agreement->id,
                'tenant_email' => $agreement->tenant_email,
                'error' => $e->getMessage(),
            ]);
        }

        // Notify the landlord (store owner) via in-app bell
        $landlord = $agreement->store?->owner;

        if ($landlord) {
            try {
                $landlord->notify(new RentalConfirmedLandlordNotification($agreement));
            } catch (\Throwable $e) {
                Log::warning('Failed to send rental-confirmed notification to landlord', [
                    'agreement_id' => $agreement->id,
                    'store_id' => $agreement->store_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
