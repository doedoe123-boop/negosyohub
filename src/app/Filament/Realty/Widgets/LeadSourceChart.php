<?php

namespace App\Filament\Realty\Widgets;

use App\Models\PropertyInquiry;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Str;

class LeadSourceChart extends ChartWidget
{
    protected static ?string $heading = 'Inquiries by Lead Source';

    protected static ?int $sort = 5;

    protected static ?string $maxHeight = '280px';

    protected int|string|array $columnSpan = 1;

    protected function getData(): array
    {
        $store = auth()->user()?->getStoreForPanel();

        if (! $store) {
            return ['datasets' => [], 'labels' => []];
        }

        $sources = PropertyInquiry::forStore($store->id)
            ->selectRaw('COALESCE(NULLIF(source, \'\'), \'unknown\') as lead_source, COUNT(*) as total')
            ->groupBy('lead_source')
            ->orderByDesc('total')
            ->pluck('total', 'lead_source');

        $palette = [
            '#4f46e5', // indigo
            '#10b981', // emerald
            '#f59e0b', // amber
            '#ef4444', // red
            '#3b82f6', // blue
            '#8b5cf6', // violet
            '#ec4899', // pink
            '#14b8a6', // teal
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Inquiries',
                    'data' => $sources->values()->toArray(),
                    'backgroundColor' => array_slice(
                        $palette,
                        0,
                        $sources->count()
                    ),
                    'borderColor' => 'transparent',
                ],
            ],
            'labels' => $sources->keys()->map(fn (string $s): string => Str::title($s))->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
