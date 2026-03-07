<?php

namespace App\Filament\LipatBahay\Resources\MovingAddOnResource\Pages;

use App\Filament\LipatBahay\Resources\MovingAddOnResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMovingAddOn extends CreateRecord
{
    protected static string $resource = MovingAddOnResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['store_id'] = auth()->user()?->getStoreForPanel()?->id;
        $data['price'] = (int) round(($data['price'] ?? 0) * 100);

        return $data;
    }
}
