<?php

namespace App\Filament\LipatBahay\Resources\MovingBookingResource\Pages;

use App\Filament\LipatBahay\Resources\MovingBookingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMovingBooking extends EditRecord
{
    protected static string $resource = MovingBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (isset($data['base_price'])) {
            $data['base_price'] = $data['base_price'] / 100;
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['base_price'])) {
            $data['base_price'] = (int) round($data['base_price'] * 100);
        }

        return $data;
    }
}
