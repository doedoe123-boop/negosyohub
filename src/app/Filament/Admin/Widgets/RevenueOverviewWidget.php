<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Order;
use App\Models\Payout;
use App\Models\SupportTicket;
use App\TicketStatus;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class RevenueOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $storeEarnings = Order::query()
            ->delivered()
            ->sum('store_earning') / 100;

        $pendingPayouts = Payout::query()
            ->where('status', Payout::STATUS_PENDING)
            ->sum('amount');

        $paidPayouts = Payout::query()
            ->where('status', Payout::STATUS_PAID)
            ->sum('amount');

        $activeOrders = Order::query()->active()->count();

        $openTickets = SupportTicket::query()
            ->where('status', TicketStatus::Open)
            ->count();

        $urgentTickets = SupportTicket::query()
            ->where('status', TicketStatus::Open)
            ->where('priority', 'urgent')
            ->count();

        return [
            Stat::make('Store Earnings', '₱'.Number::format($storeEarnings, 2))
                ->description('Total owed to stores')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('primary'),

            Stat::make('Pending Payouts', '₱'.Number::format($pendingPayouts, 2))
                ->description(Payout::pending()->count().' payouts awaiting')
                ->descriptionIcon('heroicon-o-clock')
                ->color('warning'),

            Stat::make('Paid Out', '₱'.Number::format($paidPayouts, 2))
                ->description(Payout::paid()->count().' payouts completed')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('info'),

            Stat::make('Active Orders', $activeOrders)
                ->description($this->ticketDescription($openTickets, $urgentTickets))
                ->descriptionIcon('heroicon-o-arrow-path')
                ->color($urgentTickets > 0 ? 'danger' : 'success'),
        ];
    }

    private function ticketDescription(int $open, int $urgent): string
    {
        if ($urgent > 0) {
            return "{$urgent} urgent ticket".($urgent > 1 ? 's' : '').' open';
        }

        return $open > 0 ? "{$open} support ticket".($open > 1 ? 's' : '').' open' : 'No open support tickets';
    }
}
