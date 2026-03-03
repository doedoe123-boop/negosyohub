<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; padding: 20px 0; }
        .content { background: #f9fafb; border-radius: 8px; padding: 24px; margin: 20px 0; }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 999px; font-size: 13px; font-weight: bold; }
        .badge-contacted { background: #fef3c7; color: #92400e; }
        .badge-viewing { background: #ede9fe; color: #4c1d95; }
        .badge-negotiating { background: #d1fae5; color: #065f46; }
        .badge-closed { background: #f1f5f9; color: #475569; }
        .footer { text-align: center; font-size: 12px; color: #9ca3af; margin-top: 32px; }
    </style>
</head>
<body>
    <div class="header">
        <h1 style="color: #10b981; margin: 0;">Inquiry Update</h1>
    </div>

    <p>Hi {{ $name }},</p>

    <p>We have an update on your inquiry about <strong>{{ $propertyTitle }}</strong> from <strong>{{ $storeName }}</strong>.</p>

    <div class="content">
        <p style="margin-top: 0;">
            <strong>Current Status:</strong>&nbsp;
            @php
                $badgeClass = match ($status->value) {
                    'contacted' => 'badge-contacted',
                    'viewing_scheduled' => 'badge-viewing',
                    'negotiating' => 'badge-negotiating',
                    default => 'badge-closed',
                };
            @endphp
            <span class="badge {{ $badgeClass }}">{{ $status->label() }}</span>
        </p>

        @if ($status->value === 'contacted')
            <p>One of our agents has reached out or will be contacting you shortly. Please keep an eye on your phone and email.</p>
        @elseif ($status->value === 'viewing_scheduled')
            <p>A property viewing has been scheduled for you.</p>
            @if ($viewingDate)
                <p><strong>Viewing Date &amp; Time:</strong> {{ \Carbon\Carbon::parse($viewingDate)->format('F j, Y \a\t g:i A') }}</p>
            @endif
            <p>Please make sure to be available at the scheduled time. If you need to reschedule, reply to this email or contact us directly.</p>
        @elseif ($status->value === 'negotiating')
            <p>Great news — your inquiry has progressed to the negotiation stage. Our agent will be in touch with the details.</p>
        @elseif ($status->value === 'closed')
            <p>Your inquiry has been closed. If you believe this was done in error or if you have further questions, feel free to reach out to us.</p>
        @else
            <p>Your inquiry status has been updated. Our team will follow up with you shortly.</p>
        @endif
    </div>

    <p>If you have any questions, please reply to this email or contact <strong>{{ $storeName }}</strong> directly.</p>

    <p>Best regards,<br><strong>{{ $storeName }}</strong></p>

    <div class="footer">
        <p>You received this email because you submitted a property inquiry through our platform.</p>
    </div>
</body>
</html>
