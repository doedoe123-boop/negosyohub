<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SavedSearchResultsMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * @param  \Illuminate\Database\Eloquent\Collection<int, \App\Models\Property>  $properties
     */
    public function __construct(
        public readonly \App\Models\SavedSearch $savedSearch,
        public readonly \Illuminate\Database\Eloquent\Collection $properties,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New properties matching "'.$this->savedSearch->name.'"',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.saved-search-results',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
