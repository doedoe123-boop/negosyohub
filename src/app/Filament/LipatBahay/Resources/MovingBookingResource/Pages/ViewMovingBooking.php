<?php

namespace App\Filament\LipatBahay\Resources\MovingBookingResource\Pages;

use App\Filament\LipatBahay\Resources\MovingBookingResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewMovingBooking extends ViewRecord
{
    protected static string $resource = MovingBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
