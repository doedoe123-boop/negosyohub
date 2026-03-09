<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Order;
use App\Models\Sector;
use App\OrderStatus;
use Filament\Widgets\ChartWidget;

class SectorPerformanceChart extends ChartWidget
{
    protected static ?string $heading = 'Orders & Revenue by Sector';

    protected static ?int $sort = 5;

    protected static ?string $maxHeight = '280px';

    protected function getData(): array
    {
        $labels = [];
        $orderCounts = [];
        $revenues = [];

        $colorMap = [
            'orange' => 'rgb(249, 115, 22)',
            'emerald' => 'rgb(16, 185, 129)',
            'sky' => 'rgb(14, 165, 233)',
            'violet' => 'rgb(139, 92, 246)',
            'indigo' => 'rgb(99, 102, 241)',
            'red' => 'rgb(239, 68, 68)',
            'amber' => 'rgb(245, 158, 11)',
            'green' => 'rgb(34, 197, 94)',
            'blue' => 'rgb(59, 130, 246)',
            'purple' => 'rgb(168, 85, 247)',
        ];

        $orderColors = [];
        $revenueColors = [];

        foreach (Sector::active()->get() as $sector) {
            $storeIds = \App\Models\Store::query()
                ->where('sector', $sector->slug)
                ->pluck('id');

            if ($storeIds->isEmpty()) {
                continue;
            }

            $orders = Order::query()->whereIn('store_id', $storeIds);

            $labels[] = $sector->name;
            $orderCounts[] = $orders->count();

            $revenue = (clone $orders)
                ->where('status', OrderStatus::Delivered)
                ->sum('platform_earning') / 100;
            $revenues[] = round($revenue, 2);

            $color = $colorMap[$sector->color] ?? 'rgb(107, 114, 128)';
            $orderColors[] = $color;
            $revenueColors[] = str_replace('rgb', 'rgba', str_replace(')', ', 0.6)', $color));
        }

        return [
            'datasets' => [
                [
                    'label' => 'Orders',
                    'data' => $orderCounts,
                    'backgroundColor' => $orderColors,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
