<?php

namespace App;

enum PromotionType: string
{
    case HolidaySale = 'holiday_sale';
    case SeasonalPromotion = 'seasonal_promotion';
    case MarketplaceDiscount = 'marketplace_discount';
    case FlashSale = 'flash_sale';
    case Anniversary = 'anniversary';
    case Clearance = 'clearance';

    public function label(): string
    {
        return match ($this) {
            self::HolidaySale => 'Holiday Sale',
            self::SeasonalPromotion => 'Seasonal Promotion',
            self::MarketplaceDiscount => 'Marketplace Discount',
            self::FlashSale => 'Flash Sale',
            self::Anniversary => 'Anniversary',
            self::Clearance => 'Clearance',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::HolidaySale => 'danger',
            self::SeasonalPromotion => 'success',
            self::MarketplaceDiscount => 'primary',
            self::FlashSale => 'warning',
            self::Anniversary => 'info',
            self::Clearance => 'gray',
        };
    }
}
