<?php

namespace App\Filament\Realty\Widgets;

use App\Models\PropertyAnalytic;
use Filament\Widgets\ChartWidget;

class ViewsOverTimeChart extends ChartWidget
{
    protected static ?string $heading = 'Views (Last 30 Days)';

    protected static ?int $sort = 2;

    protected static ?string $maxHeight = '280px';

    protected function getData(): array
    {
        $store = auth()->user()?->getStoreForPanel();
        if (! $store) {
            return ['datasets' => [], 'labels' => []];
        }

        $analytics = PropertyAnalytic::forStore($store->id)
            ->last30Days()
            ->selectRaw('date, SUM(views) as total_views, SUM(inquiries) as total_inquiries')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Fill in missing dates
        $labels = [];
        $views = [];
        $inquiries = [];
        $date = now()->subDays(29);

        for ($i = 0; $i < 30; $i++) {
            $dateStr = $date->format('Y-m-d');
            $labels[] = $date->format('M d');

            $row = $analytics->firstWhere('date', $dateStr);
            $views[] = $row?->total_views ?? 0;
            $inquiries[] = $row?->total_inquiries ?? 0;

            $date->addDay();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Views',
                    'data' => $views,
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                ],
                [
                    'label' => 'Inquiries',
                    'data' => $inquiries,
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
