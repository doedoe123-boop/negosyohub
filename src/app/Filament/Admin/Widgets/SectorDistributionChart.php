<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Sector;
use App\Models\Store;
use Filament\Widgets\ChartWidget;

class SectorDistributionChart extends ChartWidget
{
    protected static ?string $heading = 'Stores by Industry Sector';

    protected static ?int $sort = 3;

    protected static ?string $maxHeight = '260px';

    protected function getData(): array
    {
        $data = [];
        $labels = [];
        $colors = [];

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
            'pink' => 'rgb(236, 72, 153)',
            'teal' => 'rgb(20, 184, 166)',
            'cyan' => 'rgb(6, 182, 212)',
            'lime' => 'rgb(132, 204, 22)',
            'yellow' => 'rgb(234, 179, 8)',
        ];

        foreach (Sector::active()->get() as $sector) {
            $count = Store::query()->where('sector', $sector->slug)->count();
            $data[] = $count;
            $labels[] = $sector->name;
            $colors[] = $colorMap[$sector->color] ?? 'rgb(107, 114, 128)';
        }

        // Add unclassified
        $unclassified = Store::query()->whereNull('sector')->count();
        if ($unclassified > 0) {
            $data[] = $unclassified;
            $labels[] = 'Unclassified';
            $colors[] = 'rgb(107, 114, 128)';
        }

        return [
            'datasets' => [
                [
                    'label' => 'Stores',
                    'data' => $data,
                    'backgroundColor' => $colors,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
