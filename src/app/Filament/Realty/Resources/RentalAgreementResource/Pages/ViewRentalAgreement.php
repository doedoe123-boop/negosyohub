<?php

namespace App\Filament\Realty\Resources\RentalAgreementResource\Pages;

use App\Filament\Realty\Resources\RentalAgreementResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewRentalAgreement extends ViewRecord
{
    protected static string $resource = RentalAgreementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
