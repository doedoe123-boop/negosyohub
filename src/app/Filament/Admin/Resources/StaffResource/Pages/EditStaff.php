<?php

namespace App\Filament\Admin\Resources\StaffResource\Pages;

use App\Filament\Admin\Resources\StaffResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStaff extends EditRecord
{
    protected static string $resource = StaffResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        activity()
            ->performedOn($this->record)
            ->causedBy(auth()->user())
            ->withProperties(['action' => 'updated'])
            ->log('Staff account updated');
    }
}
