<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body>
<p>Hi {{ $booking->contact_name }},</p>

<p>This is a friendly reminder that your move is scheduled for <strong>tomorrow</strong>:</p>

<ul>
    <li><strong>Date &amp; Time:</strong> {{ $booking->scheduled_at->format('F j, Y \a\t g:i A') }}</li>
    <li><strong>Pickup:</strong> {{ $booking->pickup_address }}, {{ $booking->pickup_city }}</li>
    <li><strong>Delivery:</strong> {{ $booking->delivery_address }}, {{ $booking->delivery_city }}</li>
</ul>

<p>Please ensure someone is available at the pickup address. If you have any concerns, please contact the moving company directly.</p>

<p><a href="{{ url('/moving/' . $booking->id) }}">View Booking Details</a></p>

<p>Thank you for using Negosyo Hub!</p>
</body>
</html>
