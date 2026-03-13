<?php

namespace App\Observers;

use App\Mail\InquiryAutoResponder;
use App\Mail\InquiryStatusUpdated;
use App\Models\PropertyInquiry;
use App\Notifications\InquiryStatusUpdatedNotification;
use App\Notifications\NewInquiryNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PropertyInquiryObserver
{
    /**
     * Handle the PropertyInquiry "created" event.
     *
     * Sends an auto-responder email to the inquirer and an in-app (database)
     * notification to the agent so the bell icon rings in the realty panel.
     */
    public function created(PropertyInquiry $inquiry): void
    {
        // Auto-responder to the inquirer
        if ($inquiry->email) {
            try {
                Mail::to($inquiry->email)->queue(new InquiryAutoResponder($inquiry));
            } catch (\Throwable $e) {
                Log::warning('Failed to send inquiry auto-responder', [
                    'inquiry_id' => $inquiry->id,
                    'email' => $inquiry->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // In-app notification to the agent (store owner)
        $agent = $inquiry->store?->owner;

        if ($agent) {
            try {
                $agent->notify(new NewInquiryNotification($inquiry));
            } catch (\Throwable $e) {
                Log::warning('Failed to send new-inquiry notification to agent', [
                    'inquiry_id' => $inquiry->id,
                    'store_id' => $inquiry->store_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Handle the PropertyInquiry "updated" event.
     *
     * When the agent changes the inquiry status, notify the inquirer by email
     * and send an in-app database notification so the dashboard stays current.
     */
    public function updated(PropertyInquiry $inquiry): void
    {
        if (! $inquiry->wasChanged('status')) {
            return;
        }

        // Email notification to the inquirer
        if ($inquiry->email) {
            try {
                Mail::to($inquiry->email)->queue(new InquiryStatusUpdated($inquiry));
            } catch (\Throwable $e) {
                Log::warning('Failed to send inquiry-status-updated email', [
                    'inquiry_id' => $inquiry->id,
                    'status' => $inquiry->status?->value,
                    'email' => $inquiry->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // In-app notification to the user (if they have an account)
        $user = $inquiry->user;

        if ($user) {
            try {
                $user->notify(new InquiryStatusUpdatedNotification($inquiry));
            } catch (\Throwable $e) {
                Log::warning('Failed to send inquiry-status-updated notification to user', [
                    'inquiry_id' => $inquiry->id,
                    'user_id' => $user->id,
                    'status' => $inquiry->status?->value,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
