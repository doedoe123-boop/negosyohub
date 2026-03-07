<?php

namespace App\Filament\Realty\Resources\RentalAgreementResource\Pages;

use App\Filament\Realty\Resources\RentalAgreementResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRentalAgreement extends CreateRecord
{
    protected static string $resource = RentalAgreementResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['store_id'] = auth()->user()->getStoreForPanel()?->id;
        $data['monthly_rent'] = (int) round($data['monthly_rent'] * 100);

        if (isset($data['security_deposit']) && $data['security_deposit'] !== null) {
            $data['security_deposit'] = (int) round($data['security_deposit'] * 100);
        }

        return $data;
    }
}
