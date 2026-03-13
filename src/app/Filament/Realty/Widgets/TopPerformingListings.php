<?php

namespace App\Filament\Realty\Widgets;

use App\Models\PropertyAnalytic;
use App\SectorTemplate;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class TopPerformingListings extends BaseWidget
{
    /**
     * Property analytics are not available for rental stores.
     */
    public static function canView(): bool
    {
        return auth()->user()?->getStoreForPanel()?->template() !== SectorTemplate::Rental;
    }

    protected static ?string $heading = 'Top Performing Listings (30 Days)';

    protected static ?int $sort = 7;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $store = auth()->user()?->getStoreForPanel();
        $thirtyDaysAgo = now()->subDays(30)->toDateString();

        return $table
            ->query(
                PropertyAnalytic::query()
                    ->where('property_analytics.store_id', $store?->id)
                    ->where('date', '>=', $thirtyDaysAgo)
                    ->join('properties', 'property_analytics.property_id', '=', 'properties.id')
                    ->leftJoin('testimonials', function ($join) {
                        $join->on('properties.id', '=', 'testimonials.property_id')
                            ->where('testimonials.is_published', true);
                    })
                    ->selectRaw('properties.id, properties.title, properties.city,
                        SUM(property_analytics.views) as total_views,
                        SUM(property_analytics.inquiries) as total_inquiries,
                        SUM(property_analytics.phone_clicks) as total_phone_clicks,
                        SUM(property_analytics.email_clicks) as total_email_clicks,
                        CASE WHEN SUM(property_analytics.views) > 0
                            THEN ROUND(SUM(property_analytics.inquiries)::numeric / SUM(property_analytics.views)::numeric * 100, 1)
                            ELSE 0
                        END as conversion_rate,
                        ROUND(AVG(testimonials.rating)::numeric, 1) as avg_review,
                        COUNT(DISTINCT testimonials.id) as review_count')
                    ->groupBy('properties.id', 'properties.title', 'properties.city')
                    ->orderByDesc('total_views')
            )
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Property')
                    ->limit(35)
                    ->searchable(query: fn (Builder $query, string $search) => $query->where('properties.title', 'ilike', "%{$search}%")),

                Tables\Columns\TextColumn::make('city')
                    ->sortable(query: fn (Builder $query, string $direction) => $query->orderBy('properties.city', $direction)),

                Tables\Columns\TextColumn::make('total_views')
                    ->label('Views')
                    ->numeric()
                    ->sortable()
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('total_inquiries')
                    ->label('Inquiries')
                    ->numeric()
                    ->sortable()
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('conversion_rate')
                    ->label('Conversion')
                    ->suffix('%')
                    ->sortable()
                    ->alignEnd()
                    ->color(fn ($state): string => match (true) {
                        $state >= 5 => 'success',
                        $state >= 2 => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('avg_review')
                    ->label('Avg Review')
                    ->formatStateUsing(function ($state, $record): string {
                        if (! $state) {
                            return '—';
                        }

                        return number_format((float) $state, 1).' ★ ('.$record->review_count.')';
                    })
                    ->sortable()
                    ->alignEnd()
                    ->color(fn ($state): string => match (true) {
                        ! $state => 'gray',
                        (float) $state >= 4.0 => 'success',
                        (float) $state >= 3.0 => 'warning',
                        default => 'danger',
                    }),
            ])
            ->paginated([5]);
    }
}
