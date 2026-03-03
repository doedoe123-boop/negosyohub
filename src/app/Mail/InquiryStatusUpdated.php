<?php

namespace App\Mail;

use App\Models\PropertyInquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Notifies the inquirer by email when their inquiry status is updated by the agent.
 *
 * This is the real estate equivalent of the OrderStatusUpdated notification for
 * e-commerce. Sent to the inquirer's email address on every meaningful pipeline
 * transition: Contacted, Viewing Scheduled, Negotiating, Closed.
 */
class InquiryStatusUpdated extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public PropertyInquiry $inquiry,
    ) {}

    public function envelope(): Envelope
    {
        $status = $this->inquiry->status->label();
        $property = $this->inquiry->property?->title ?? 'your inquiry';

        return new Envelope(
            subject: "Update on {$property} — {$status}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.inquiry-status-updated',
            with: [
                'name' => $this->inquiry->name,
                'propertyTitle' => $this->inquiry->property?->title ?? 'the property',
                'storeName' => $this->inquiry->store?->name ?? 'our team',
                'status' => $this->inquiry->status,
                'viewingDate' => $this->inquiry->viewing_date,
            ],
        );
    }
}
