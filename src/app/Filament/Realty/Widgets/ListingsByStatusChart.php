<?php

namespace App\Filament\Realty\Widgets;

use App\Models\Property;
use App\PropertyStatus;
use Filament\Widgets\ChartWidget;

class ListingsByStatusChart extends ChartWidget
{
    protected static ?string $heading = 'Listings by Status';

    protected static ?int $sort = 4;

    protected static ?string $maxHeight = '280px';

    protected int|string|array $columnSpan = 1;

    protected function getData(): array
    {
        $store = auth()->user()?->getStoreForPanel();

        if (! $store) {
            return ['datasets' => [], 'labels' => []];
        }

        $counts = [];
        $labels = [];
        $colors = [];

        $colorMap = [
            'draft' => '#9ca3af',
            'active' => '#10b981',
            'under_offer' => '#f59e0b',
            'sold' => '#ef4444',
            'rented' => '#3b82f6',
            'archived' => '#6b7280',
        ];

        foreach (PropertyStatus::cases() as $status) {
            $count = Property::forStore($store->id)
                ->where('status', $status)
                ->count();

            if ($count > 0) {
                $labels[] = $status->label();
                $counts[] = $count;
                $colors[] = $colorMap[$status->value] ?? '#6b7280';
            }
        }

        return [
            'datasets' => [
                [
                    'data' => $counts,
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
