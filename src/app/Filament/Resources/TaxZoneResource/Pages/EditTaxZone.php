<?php

namespace App\Filament\Resources\TaxZoneResource\Pages;

use App\Filament\Resources\TaxZoneResource;
use Filament\Actions;
use Lunar\Admin\Support\Pages\BaseEditRecord;

class EditTaxZone extends BaseEditRecord
{
    protected static string $resource = TaxZoneResource::class;

    protected function getDefaultHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
