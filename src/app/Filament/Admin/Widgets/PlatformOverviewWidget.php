<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use App\StoreStatus;
use App\UserRole;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class PlatformOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalStores = Store::query()->count();
        $activeStores = Store::query()->where('status', StoreStatus::Approved)->count();
        $pendingStores = Store::query()->where('status', StoreStatus::Pending)->count();

        $totalCustomers = User::query()->where('role', UserRole::Customer)->count();
        $newCustomersThisMonth = User::query()
            ->where('role', UserRole::Customer)
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

        $totalOrders = Order::query()->count();
        $deliveredOrders = Order::query()->delivered()->count();

        $totalRevenue = Order::query()
            ->delivered()
            ->sum('platform_earning') / 100;

        return [
            Stat::make('Total Stores', Number::format($totalStores))
                ->description("{$activeStores} active · {$pendingStores} pending")
                ->descriptionIcon('heroicon-o-building-storefront')
                ->color('primary'),

            Stat::make('Total Customers', Number::format($totalCustomers))
                ->description("{$newCustomersThisMonth} new this month")
                ->descriptionIcon('heroicon-o-users')
                ->color('success'),

            Stat::make('Total Orders', Number::format($totalOrders))
                ->description("{$deliveredOrders} delivered")
                ->descriptionIcon('heroicon-o-shopping-bag')
                ->color('info'),

            Stat::make('Platform Revenue', '₱'.Number::format($totalRevenue, 2))
                ->description('Commission from delivered orders')
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->color('success'),
        ];
    }
}
