<?php

namespace App\Filament\Admin\Resources\StaffResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ActivityLogRelationManager extends RelationManager
{
    protected static string $relationship = 'activities';

    protected static ?string $title = 'Audit Trail';

    protected static ?string $icon = 'heroicon-o-clipboard-document-list';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                Tables\Columns\TextColumn::make('description')
                    ->label('Action')
                    ->weight('bold')
                    ->searchable(),
                Tables\Columns\TextColumn::make('causer.name')
                    ->label('Performed By')
                    ->default('System')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('properties.action')
                    ->label('Type')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'info',
                        'deactivated' => 'danger',
                        'reactivated' => 'success',
                        'password_reset' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => $state
                        ? ucwords(str_replace('_', ' ', $state))
                        : '—'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('When')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50]);
    }
}
