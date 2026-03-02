<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Store;
use App\StoreStatus;
use Filament\Widgets\ChartWidget;

class StoreStatusChart extends ChartWidget
{
    protected static ?string $heading = 'Store Status Breakdown';

    protected static ?int $sort = 4;

    protected static ?string $maxHeight = '260px';

    protected function getData(): array
    {
        $approved = Store::query()->where('status', StoreStatus::Approved)->count();
        $pending = Store::query()->where('status', StoreStatus::Pending)->count();
        $rejected = Store::query()->where('status', StoreStatus::Rejected)->count();
        $suspended = Store::query()->where('status', StoreStatus::Suspended)->count();

        return [
            'datasets' => [
                [
                    'label' => 'Stores',
                    'data' => [$approved, $pending, $rejected, $suspended],
                    'backgroundColor' => [
                        'rgb(16, 185, 129)',  // emerald - approved
                        'rgb(245, 158, 11)', // amber - pending
                        'rgb(239, 68, 68)',  // red - rejected
                        'rgb(244, 63, 94)',  // rose - suspended
                    ],
                ],
            ],
            'labels' => ['Approved', 'Pending', 'Rejected', 'Suspended'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
