<?php

namespace App\Notifications;

use App\Models\RentalAgreement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Email notification sent to the tenant when a landlord confirms a rental agreement.
 *
 * Includes a call-to-action button directing them to browse Lipat Bahay
 * (house moving) services so they can book a mover for their move-in date.
 */
class RentalConfirmedTenantNotification extends Notification implements ShouldQueue
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
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $property = $this->agreement->property?->title ?? 'your property';
        $moveInDate = $this->agreement->move_in_date->format('F j, Y');
        $moversUrl = env('FRONTEND_URL', 'http://localhost:5173').'/movers?rental_id='.$this->agreement->id;

        $address = $this->agreement->property?->fullLocation() ?? 'the property address';
        $monthlyRent = '₱'.number_format($this->agreement->monthly_rent / 100, 2);
        $securityDeposit = $this->agreement->security_deposit ? ('₱'.number_format($this->agreement->security_deposit / 100, 2)) : 'None / N/A';
        $leaseTerm = $this->agreement->lease_term_months ? "{$this->agreement->lease_term_months} months" : 'Not specified';

        return (new MailMessage)
            ->subject('Your Rental Agreement is Confirmed!')
            ->greeting('Congratulations, '.$this->agreement->tenant_name.'!')
            ->line("Your rental agreement for **{$property}** has been confirmed.")
            ->line("**Move-in date:** {$moveInDate}")
            ->line("**Address:** {$address}")
            ->line("**Monthly Rent:** {$monthlyRent}")
            ->line("**Security Deposit:** {$securityDeposit}")
            ->line("**Lease Term:** {$leaseTerm}")
            ->line('Need help moving? Browse trusted Lipat Bahay movers in your area.')
            ->action('Find Movers', $moversUrl)
            ->line('Thank you for using '.config('app.name').'!');
    }
}
