<?php

namespace App\Filament\Admin\Resources\FeaturedListingResource\Pages;

use App\Filament\Admin\Resources\FeaturedListingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFeaturedListing extends EditRecord
{
    protected static string $resource = FeaturedListingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
