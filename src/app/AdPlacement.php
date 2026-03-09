<?php

namespace App;

enum AdPlacement: string
{
    case HomeBanner = 'home_banner';
    case SectorPage = 'sector_page';
    case SearchResults = 'search_results';
    case FeaturedSection = 'featured_section';
    case StorePage = 'store_page';
    case CategoryPage = 'category_page';

    public function label(): string
    {
        return match ($this) {
            self::HomeBanner => 'Homepage Banner',
            self::SectorPage => 'Sector Page',
            self::SearchResults => 'Search Results',
            self::FeaturedSection => 'Featured Section',
            self::StorePage => 'Store Page',
            self::CategoryPage => 'Category Page',
        };
    }
}
