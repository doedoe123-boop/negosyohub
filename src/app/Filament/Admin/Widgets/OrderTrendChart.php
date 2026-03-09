<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class OrderTrendChart extends ChartWidget
{
    protected static ?string $heading = 'Orders — Last 30 Days';

    protected static ?int $sort = 6;

    protected static ?string $maxHeight = '260px';

    protected function getData(): array
    {
        $days = collect(range(29, 0))->map(fn (int $i): Carbon => now()->subDays($i)->startOfDay());

        $orders = Order::query()
            ->where('placed_at', '>=', now()->subDays(30)->startOfDay())
            ->selectRaw('DATE(placed_at) as date, COUNT(*) as total')
            ->groupByRaw('DATE(placed_at)')
            ->pluck('total', 'date');

        return [
            'datasets' => [
                [
                    'label' => 'Orders',
                    'data' => $days->map(fn (Carbon $day) => $orders->get($day->toDateString(), 0))->values()->all(),
                    'borderColor' => 'rgb(99, 102, 241)',
                    'backgroundColor' => 'rgba(99, 102, 241, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $days->map(fn (Carbon $day): string => $day->format('M d'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
