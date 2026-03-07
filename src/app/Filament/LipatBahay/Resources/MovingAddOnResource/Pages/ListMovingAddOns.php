<?php

namespace App\Filament\LipatBahay\Resources\MovingAddOnResource\Pages;

use App\Filament\LipatBahay\Resources\MovingAddOnResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMovingAddOns extends ListRecords
{
    protected static string $resource = MovingAddOnResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
