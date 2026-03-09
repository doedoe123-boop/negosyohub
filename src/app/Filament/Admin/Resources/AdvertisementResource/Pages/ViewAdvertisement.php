<?php

namespace App\Filament\Admin\Resources\AdvertisementResource\Pages;

use App\Filament\Admin\Resources\AdvertisementResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAdvertisement extends ViewRecord
{
    protected static string $resource = AdvertisementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
