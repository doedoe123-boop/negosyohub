<?php

namespace App\Filament\Admin\Widgets;

use App\CampaignStatus;
use App\Models\Advertisement;
use App\Models\Campaign;
use App\Models\Coupon;
use App\Models\FeaturedListing;
use App\Models\Promotion;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MarketingInsightsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 9;

    protected function getStats(): array
    {
        $activeCampaigns = Campaign::query()
            ->where('status', CampaignStatus::Active)
            ->count();
        $totalCampaigns = Campaign::query()->count();

        $activePromotions = Promotion::query()->active()->count();

        $activeCoupons = Coupon::query()->active()->count();
        $totalCouponUses = Coupon::query()->sum('times_used');

        $activeAds = Advertisement::query()->active()->count();

        $activeFeatured = FeaturedListing::query()->active()->count();

        return [
            Stat::make('Active Campaigns', $activeCampaigns)
                ->description("{$totalCampaigns} total campaigns")
                ->descriptionIcon('heroicon-o-rocket-launch')
                ->color('primary'),

            Stat::make('Active Promotions', $activePromotions)
                ->description("{$activeAds} active ads")
                ->descriptionIcon('heroicon-o-fire')
                ->color('danger'),

            Stat::make('Active Coupons', $activeCoupons)
                ->description("{$totalCouponUses} total redemptions")
                ->descriptionIcon('heroicon-o-ticket')
                ->color('warning'),

            Stat::make('Featured Listings', $activeFeatured)
                ->description('Currently promoted')
                ->descriptionIcon('heroicon-o-star')
                ->color('info'),
        ];
    }
}
