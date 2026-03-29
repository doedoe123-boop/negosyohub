<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class SellerEmailVerificationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);
        $storeName = $notifiable->store?->name ?? config('app.name');

        return (new MailMessage)
            ->subject('Verify Your Seller Email Address')
            ->greeting("Hello {$notifiable->name}!")
            ->line("Your seller account for **{$storeName}** is almost ready.")
            ->line('Please verify your email address before signing in to your store dashboard.')
            ->action('Verify Email Address', $verificationUrl)
            ->line('If you did not create this seller account, no further action is required.');
    }

    private function verificationUrl(object $notifiable): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ],
        );
    }
}
