<?php

namespace App;

/**
 * Predefined behavioral templates for industry sectors.
 *
 * Each template determines which Filament panel a store uses,
 * which features are available, and how the frontend renders
 * the store. Admins can create unlimited sectors but must
 * assign one of these templates.
 */
enum SectorTemplate: string
{
    case Ecommerce = 'ecommerce';
    case RealEstate = 'real_estate';
    case Rental = 'rental';
    case Service = 'service';
    case Logistics = 'logistics';

    public function label(): string
    {
        return match ($this) {
            self::Ecommerce => 'E-Commerce',
            self::RealEstate => 'Real Estate',
            self::Rental => 'Rental / Paupahan',
            self::Service => 'Service',
            self::Logistics => 'Logistics / Moving',
        };
    }

    /**
     * The Filament panel ID this template routes to.
     */
    public function panelId(): string
    {
        return match ($this) {
            self::Ecommerce, self::Service => 'lunar',
            self::RealEstate, self::Rental => 'realty',
            self::Logistics => 'lipat-bahay',
        };
    }

    /**
     * The Heroicon name for this template.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Ecommerce => 'heroicon-o-shopping-cart',
            self::RealEstate => 'heroicon-o-home-modern',
            self::Rental => 'heroicon-o-building-office-2',
            self::Service => 'heroicon-o-wrench-screwdriver',
            self::Logistics => 'heroicon-o-truck',
        };
    }

    /**
     * Features available to stores using this template.
     *
     * @return list<string>
     */
    public function supportedFeatures(): array
    {
        return match ($this) {
            self::Ecommerce => ['products', 'cart', 'orders', 'categories', 'reviews'],
            self::RealEstate => ['properties', 'agent_profile', 'developments', 'open_houses', 'mortgage_calculator', 'property_analytics'],
            self::Rental => ['properties', 'agent_profile', 'rental_agreements'],
            self::Service => ['products', 'cart', 'orders', 'reviews'],
            self::Logistics => ['moving_bookings', 'add_ons', 'fleet'],
        };
    }

    /**
     * CSS gradient class string for frontend theming.
     */
    public function themeGradient(): string
    {
        return match ($this) {
            self::Ecommerce => 'from-brand-600 via-brand-700 to-brand-800',
            self::RealEstate => 'from-[#0F2044] via-[#162d5a] to-[#0F2044]',
            self::Rental => 'from-sky-600 via-sky-700 to-sky-800',
            self::Service => 'from-slate-700 via-slate-800 to-slate-900',
            self::Logistics => 'from-violet-600 via-violet-700 to-violet-800',
        };
    }

    /**
     * Search categories this template contributes to.
     *
     * Determines which result sections appear in global search.
     *
     * @return list<string>
     */
    public function searchCategories(): array
    {
        return match ($this) {
            self::Ecommerce, self::Service => ['stores', 'products'],
            self::RealEstate, self::Rental => ['stores', 'properties'],
            self::Logistics => ['stores'],
        };
    }
}
