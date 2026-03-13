<?php

namespace App\Notifications;

use App\Models\RentalAgreement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LandlordAgreementResponseNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public RentalAgreement $agreement,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $property = $this->agreement->property?->title ?? 'the property';
        $agreementsUrl = env('FRONTEND_URL', 'http://localhost:5173').'/account/agreements';

        return (new MailMessage)
            ->subject('Update: Landlord Responded to Your Questions')
            ->greeting('Hello, '.$this->agreement->tenant_name.'!')
            ->line("The landlord for **{$property}** has responded to your questions regarding the rental agreement.")
            ->line('**Landlord\'s Response:**')
            ->line('"'.$this->agreement->landlord_response.'"')
            ->line('Please review the response and the agreement terms.')
            ->action('Review & Sign Agreement', $agreementsUrl)
            ->line('Thank you for using '.config('app.name').'!');
    }
}
