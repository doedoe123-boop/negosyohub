<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Store;
use App\StoreStatus;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentRegistrationsWidget extends TableWidget
{
    protected static ?string $heading = 'Recent Store Registrations';

    protected static ?int $sort = 7;

    protected int|string|array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Store::query()
                    ->with('owner')
                    ->latest()
                    ->limit(6)
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Store')
                    ->limit(24),

                Tables\Columns\TextColumn::make('sector')
                    ->label('Sector')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state
                        ? ucwords(str_replace('_', ' ', $state))
                        : 'Unset')
                    ->color('gray'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => StoreStatus::tryFrom($state)?->name ?? ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        StoreStatus::Approved->value => 'success',
                        StoreStatus::Pending->value => 'warning',
                        StoreStatus::Rejected->value => 'danger',
                        StoreStatus::Suspended->value => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registered')
                    ->since(),
            ])
            ->paginated(false);
    }
}
