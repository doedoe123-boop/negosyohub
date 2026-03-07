<?php

namespace App\Filament\LipatBahay\Resources\MovingAddOnResource\Pages;

use App\Filament\LipatBahay\Resources\MovingAddOnResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMovingAddOn extends EditRecord
{
    protected static string $resource = MovingAddOnResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (isset($data['price'])) {
            $data['price'] = $data['price'] / 100;
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['price'])) {
            $data['price'] = (int) round($data['price'] * 100);
        }

        return $data;
    }
}
