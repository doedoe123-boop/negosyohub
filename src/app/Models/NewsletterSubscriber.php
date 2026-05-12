<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsletterSubscriber extends Model
{
    protected $fillable = [
        'email',
        'source',
        'subscribed_at',
        'brevo_contact_id',
        'brevo_sync_status',
        'brevo_synced_at',
        'welcome_email_status',
        'welcome_sent_at',
        'welcome_delivered_at',
        'welcome_opened_at',
        'welcome_bounced_at',
        'welcome_resend_requested_at',
        'last_brevo_event',
        'last_brevo_error',
    ];

    protected function casts(): array
    {
        return [
            'subscribed_at' => 'datetime',
            'brevo_synced_at' => 'datetime',
            'welcome_sent_at' => 'datetime',
            'welcome_delivered_at' => 'datetime',
            'welcome_opened_at' => 'datetime',
            'welcome_bounced_at' => 'datetime',
            'welcome_resend_requested_at' => 'datetime',
        ];
    }
}
