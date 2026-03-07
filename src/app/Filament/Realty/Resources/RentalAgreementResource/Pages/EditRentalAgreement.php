<?php

namespace App\Filament\Realty\Resources\RentalAgreementResource\Pages;

use App\Filament\Realty\Resources\RentalAgreementResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRentalAgreement extends EditRecord
{
    protected static string $resource = RentalAgreementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Convert centavos back to pesos for the form
        $data['monthly_rent'] = $data['monthly_rent'] / 100;

        if (isset($data['security_deposit']) && $data['security_deposit'] !== null) {
            $data['security_deposit'] = $data['security_deposit'] / 100;
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['monthly_rent'] = (int) round($data['monthly_rent'] * 100);

        if (isset($data['security_deposit']) && $data['security_deposit'] !== null) {
            $data['security_deposit'] = (int) round($data['security_deposit'] * 100);
        }

        return $data;
    }
}
