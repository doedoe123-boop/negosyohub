<?php

namespace App\Filament\Admin\Resources\FeaturedListingResource\Pages;

use App\Filament\Admin\Resources\FeaturedListingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFeaturedListings extends ListRecords
{
    protected static string $resource = FeaturedListingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
