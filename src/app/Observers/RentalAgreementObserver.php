<?php

namespace App\Observers;

use App\Models\RentalAgreement;
use App\Notifications\LandlordAgreementResponseNotification;
use App\Notifications\RentalAgreementPendingNotification;
use App\Notifications\RentalAgreementQuestionNotification;
use App\Notifications\RentalConfirmedLandlordNotification;
use App\Notifications\RentalConfirmedTenantNotification;
use App\PropertyStatus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class RentalAgreementObserver
{
    /**
     * Handle the RentalAgreement "created" event.
     */
    public function created(RentalAgreement $agreement): void
    {
        // Notify the tenant via email that they have a pending agreement to review
        try {
            Notification::route('mail', $agreement->tenant_email)
                ->notify(new RentalAgreementPendingNotification($agreement));
        } catch (\Throwable $e) {
            Log::warning('Failed to send pending-rental email to tenant', [
                'agreement_id' => $agreement->id,
                'tenant_email' => $agreement->tenant_email,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the RentalAgreement "updated" event.
     */
    public function updated(RentalAgreement $agreement): void
    {
        // Check if the tenant just signed the agreement
        if ($agreement->isDirty('status') && $agreement->status === 'signed') {
            // Mark property as rented
            if ($agreement->property) {
                $agreement->property->update(['status' => PropertyStatus::Rented]);
            }

            // Notify the tenant (confirmed)
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

        // Check if the tenant just asked a question (status changed to negotiating)
        if ($agreement->isDirty('status') && $agreement->status === 'negotiating') {
            $landlord = $agreement->store?->owner;
            if ($landlord) {
                try {
                    $landlord->notify(new RentalAgreementQuestionNotification($agreement));
                } catch (\Throwable $e) {
                    Log::warning('Failed to send negotiation notification to landlord', [
                        'agreement_id' => $agreement->id,
                        'store_id' => $agreement->store_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        // Check if the landlord just replied to questions
        if ($agreement->isDirty('landlord_response') && ! empty($agreement->landlord_response)) {
            try {
                Notification::route('mail', $agreement->tenant_email)
                    ->notify(new LandlordAgreementResponseNotification($agreement));
            } catch (\Throwable $e) {
                Log::warning('Failed to send landlord-response email to tenant', [
                    'agreement_id' => $agreement->id,
                    'tenant_email' => $agreement->tenant_email,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
