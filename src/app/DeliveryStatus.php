<?php

namespace App;

enum DeliveryStatus: string
{
    case Pending = 'pending';
    case AwaitingBooking = 'awaiting_booking';
    case DriverAssigned = 'driver_assigned';
    case PickedUp = 'picked_up';
    case InTransit = 'in_transit';
    case Delivered = 'delivered';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::AwaitingBooking => 'Awaiting Booking',
            self::DriverAssigned => 'Driver Assigned',
            self::PickedUp => 'Picked Up by Driver',
            self::InTransit => 'Out for Delivery',
            self::Delivered => 'Delivered',
            self::Failed => 'Delivery Failed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending, self::AwaitingBooking => 'warning',
            self::DriverAssigned => 'info',
            self::PickedUp, self::InTransit => 'primary',
            self::Delivered => 'success',
            self::Failed, self::Cancelled => 'danger',
        };
    }

    public function customerLabel(): string
    {
        return match ($this) {
            self::Pending => 'Preparing',
            self::AwaitingBooking => 'Preparing',
            self::DriverAssigned => 'Ready for Pickup',
            self::PickedUp => 'Picked Up by Driver',
            self::InTransit => 'Out for Delivery',
            self::Delivered => 'Delivered',
            self::Failed => 'Delivery Delayed',
            self::Cancelled => 'Cancelled',
        };
    }
}
