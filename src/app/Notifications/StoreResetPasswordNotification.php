<?php

namespace App\Notifications;

use App\Models\Store;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StoreResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $token,
        public Store $store,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = $this->buildResetUrl($notifiable);

        return (new MailMessage)
            ->subject("Reset Your {$this->store->sectorLabel()} Password")
            ->greeting("Hello {$notifiable->name}!")
            ->line("You are receiving this email because we received a password reset request for your account on **{$this->store->name}**.")
            ->action('Reset Password', $url)
            ->line('This password reset link will expire in 60 minutes.')
            ->line('If you did not request a password reset, no further action is required.');
    }

    private function buildResetUrl(object $notifiable): string
    {
        $scheme = parse_url(config('app.url'), PHP_URL_SCHEME) ?: 'http';
        $domain = config('app.domain');
        $port = parse_url(config('app.url'), PHP_URL_PORT);

        $base = $scheme.'://'.$this->store->slug.'.'.$domain;

        if ($port) {
            $base .= ':'.$port;
        }

        return $base.'/portal/'.$this->store->login_token.'/reset-password/'.$this->token.'?email='.urlencode($notifiable->getEmailForPasswordReset());
    }
}
