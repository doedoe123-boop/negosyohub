<?php

namespace App\Notifications;

use App\InquiryStatus;
use App\Models\PropertyInquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Database notification sent to the customer when the agent/landlord
 * changes the status of their property inquiry (e.g. contacted, viewing scheduled).
 */
class InquiryStatusUpdatedNotification extends Notification implements ShouldQueue
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
        $status = $this->inquiry->status;

        return [
            'title' => 'Inquiry Update',
            'body' => $this->buildBody($property, $status),
            'icon' => 'heroicon-o-home-modern',
            'icon_color' => $status->color(),
            'property_slug' => $this->inquiry->property?->slug,
            'inquiry_id' => $this->inquiry->id,
        ];
    }

    private function buildBody(string $property, InquiryStatus $status): string
    {
        return match ($status) {
            InquiryStatus::Contacted => "The agent has reviewed your inquiry about {$property} and will reach out soon.",
            InquiryStatus::ViewingScheduled => "A viewing has been scheduled for {$property}. Check your email for details.",
            InquiryStatus::Negotiating => "Negotiation is in progress for {$property}.",
            InquiryStatus::Closed => "Your inquiry about {$property} has been closed.",
            default => "Your inquiry about {$property} has been updated to {$status->label()}.",
        };
    }
}
