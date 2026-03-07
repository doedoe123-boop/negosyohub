<?php

namespace App\Filament\Realty\Resources\RentalAgreementResource\Pages;

use App\Filament\Realty\Resources\RentalAgreementResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRentalAgreements extends ListRecords
{
    protected static string $resource = RentalAgreementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->mutateFormDataUsing(function (array $data): array {
                    $data['store_id'] = auth()->user()->getStoreForPanel()?->id;
                    $data['monthly_rent'] = (int) round($data['monthly_rent'] * 100);
                    if (isset($data['security_deposit'])) {
                        $data['security_deposit'] = (int) round($data['security_deposit'] * 100);
                    }

                    return $data;
                }),
        ];
    }
}
