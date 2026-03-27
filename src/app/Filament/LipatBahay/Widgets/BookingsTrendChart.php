<?php

namespace App\Filament\LipatBahay\Widgets;

use App\Models\MovingBooking;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Illuminate\Support\Carbon;

class BookingsTrendChart extends ChartWidget
{
    protected static ?string $heading = 'Bookings Over Time';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $store = auth()->user()?->getStoreForPanel();

        if (! $store) {
            return [];
        }

        $data = Trend::query(MovingBooking::where('store_id', $store->id))
            ->between(
                start: now()->subDays(30),
                end: now(),
            )
            ->perDay()
            ->count();

        return [
            'datasets' => [
                [
                    'label' => 'Bookings',
                    'data' => $data->map(fn (TrendValue $value) => $value->aggregate),
                ],
            ],
            'labels' => $data->map(fn (TrendValue $value) => Carbon::parse($value->date)->format('M j')),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
