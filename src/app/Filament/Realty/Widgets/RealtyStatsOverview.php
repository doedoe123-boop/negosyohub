<?php

namespace App\Filament\Realty\Widgets;

use App\Models\Development;
use App\Models\OpenHouse;
use App\Models\Property;
use App\Models\PropertyInquiry;
use App\Models\Testimonial;
use App\SectorTemplate;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RealtyStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $store = auth()->user()?->getStoreForPanel();

        if (! $store) {
            return [];
        }

        $totalListings = Property::forStore($store->id)->count();
        $activeListings = Property::forStore($store->id)->active()->count();
        $newInquiries = PropertyInquiry::forStore($store->id)->new()->count();
        $totalViews = Property::forStore($store->id)->sum('views_count');
        $publishedReviews = Testimonial::forStore($store->id)->published()->count();
        $avgRating = Testimonial::forStore($store->id)->published()->avg('rating');
        $avgRatingFormatted = $avgRating ? number_format($avgRating, 1) : '—';

        // Reviews last 7 days for trend
        $recentReviews = Testimonial::forStore($store->id)
            ->published()
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        $isRental = $store->template() === SectorTemplate::Rental;

        $stats = [
            Stat::make('Active Listings', $activeListings)
                ->description("{$totalListings} total")
                ->icon('heroicon-o-home-modern')
                ->color('success'),
        ];

        if (! $isRental) {
            $totalDevelopments = Development::forStore($store->id)->active()->count();
            $stats[] = Stat::make('Developments', $totalDevelopments)
                ->description('Active projects')
                ->icon('heroicon-o-building-office-2')
                ->color('warning');
        }

        $stats[] = Stat::make('New Inquiries', $newInquiries)
            ->description('Awaiting response')
            ->icon('heroicon-o-chat-bubble-left-right')
            ->color($newInquiries > 0 ? 'danger' : 'gray');

        $stats[] = Stat::make('Avg Rating', $avgRatingFormatted)
            ->description("{$publishedReviews} reviews")
            ->icon('heroicon-o-star')
            ->color($avgRating >= 4 ? 'success' : ($avgRating >= 3 ? 'warning' : 'danger'));

        if (! $isRental) {
            $upcomingOpenHouses = OpenHouse::forStore($store->id)->upcoming()->count();
            $stats[] = Stat::make('Open Houses', $upcomingOpenHouses)
                ->description('Upcoming events')
                ->icon('heroicon-o-calendar-days')
                ->color('info');
        }

        $stats[] = Stat::make('Total Views', number_format($totalViews))
            ->description($recentReviews > 0 ? "{$recentReviews} new reviews this week" : 'Across all listings')
            ->icon('heroicon-o-eye')
            ->color('info');

        return $stats;
    }
}
