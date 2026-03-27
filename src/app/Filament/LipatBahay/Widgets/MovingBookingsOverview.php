<?php

namespace App\Filament\LipatBahay\Widgets;

use App\Models\MovingBooking;
use App\MovingBookingStatus;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MovingBookingsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $store = auth()->user()?->getStoreForPanel();

        if (! $store) {
            return [];
        }

        $query = MovingBooking::where('store_id', $store->id);

        $totalBookings = (clone $query)->count();
        $pendingBookings = (clone $query)->where('status', MovingBookingStatus::Pending)->count();

        $totalRevenue = (clone $query)
            ->where('status', MovingBookingStatus::Completed)
            ->sum('total_price') / 100;

        return [
            Stat::make('Total Bookings', number_format($totalBookings))
                ->icon('heroicon-o-truck'),
            Stat::make('Pending Bookings', number_format($pendingBookings))
                ->icon('heroicon-o-clock')
                ->color('warning'),
            Stat::make('Completed Revenue', '₱'.number_format($totalRevenue, 2))
                ->icon('heroicon-o-banknotes')
                ->color('success'),
        ];
    }
}
