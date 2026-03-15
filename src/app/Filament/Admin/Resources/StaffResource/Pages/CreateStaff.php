<?php

namespace App\Filament\Admin\Resources\StaffResource\Pages;

use App\Filament\Admin\Resources\StaffResource;
use App\UserRole;
use Filament\Resources\Pages\CreateRecord;

class CreateStaff extends CreateRecord
{
    protected static string $resource = StaffResource::class;

    /**
     * Automatically set the role to Admin so this user
     * can access the admin panel.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['role'] = UserRole::Admin->value;

        return $data;
    }

    protected function afterCreate(): void
    {
        activity()
            ->performedOn($this->record)
            ->causedBy(auth()->user())
            ->withProperties(['action' => 'created'])
            ->log('Staff account created');
    }
}
