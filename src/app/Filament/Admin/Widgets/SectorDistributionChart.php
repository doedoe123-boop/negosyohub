<?php

namespace App\Filament\Admin\Widgets;

use App\IndustrySector;
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
            'construction' => 'rgb(245, 158, 11)',
            'technology' => 'rgb(59, 130, 246)',
            'food_and_beverage' => 'rgb(249, 115, 22)',
            'healthcare' => 'rgb(239, 68, 68)',
            'chemicals' => 'rgb(139, 92, 246)',
            'logistics' => 'rgb(99, 102, 241)',
            'real_estate' => 'rgb(16, 185, 129)',
            'agriculture' => 'rgb(34, 197, 94)',
        ];

        foreach (IndustrySector::cases() as $sector) {
            $count = Store::query()->where('sector', $sector->value)->count();
            $data[] = $count;
            $labels[] = $sector->label();
            $colors[] = $colorMap[$sector->value] ?? 'rgb(107, 114, 128)';
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
