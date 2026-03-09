<?php

namespace App\Filament\Admin\Resources\FeaturedListingResource\Pages;

use App\Filament\Admin\Resources\FeaturedListingResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewFeaturedListing extends ViewRecord
{
    protected static string $resource = FeaturedListingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
