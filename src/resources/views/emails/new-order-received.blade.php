<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; padding: 20px 0; }
        .content { background: #f9fafb; border-radius: 8px; padding: 24px; margin: 20px 0; }
        .order-ref { background: #eef2ff; border: 1px solid #c7d2fe; border-radius: 6px; padding: 12px 16px; margin: 12px 0; font-family: monospace; font-size: 16px; font-weight: bold; color: #4338ca; text-align: center; letter-spacing: 2px; }
        .summary-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e5e7eb; }
        .summary-row:last-child { border-bottom: none; font-weight: bold; }
        .earning { color: #059669; font-size: 18px; font-weight: bold; }
        .btn { display: inline-block; background: #4f46e5; color: #fff; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: 600; margin-top: 16px; }
        .footer { text-align: center; font-size: 12px; color: #9ca3af; margin-top: 32px; }
    </style>
</head>
<body>
    <div class="header">
        <h1 style="color: #4f46e5; margin: 0;">🛍️ New Order Received!</h1>
    </div>

    <p>Hi {{ $ownerName }},</p>

    <p>Great news — <strong>{{ $storeName }}</strong> just received a new order. Please confirm it as soon as possible to keep your response time high.</p>

    <div class="content">
        <h3 style="margin-top: 0;">Order Details</h3>

        <div class="order-ref">{{ $orderReference }}</div>

        <table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 16px;">
            <tr>
                <td style="padding: 8px 0; border-bottom: 1px solid #e5e7eb; color: #6b7280;">Order Total</td>
                <td style="padding: 8px 0; border-bottom: 1px solid #e5e7eb; text-align: right;"><strong>{{ $currencyCode }} {{ $total }}</strong></td>
            </tr>
            <tr>
                <td style="padding: 8px 0; color: #6b7280;">Your Earnings (after commission)</td>
                <td style="padding: 8px 0; text-align: right;" class="earning">{{ $currencyCode }} {{ $earning }}</td>
            </tr>
        </table>
    </div>

    @if ($dashboardUrl)
    <p style="text-align: center;">
        <a href="{{ $dashboardUrl }}" class="btn">View Order in Dashboard</a>
    </p>
    @endif

    <p style="font-size: 13px; color: #6b7280;">Please confirm the order promptly. Unconfirmed orders may be automatically cancelled after 24 hours.</p>

    <p>Best regards,<br><strong>The {{ config('app.name') }} Team</strong></p>

    <div class="footer">
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
    </div>
</body>
</html>
