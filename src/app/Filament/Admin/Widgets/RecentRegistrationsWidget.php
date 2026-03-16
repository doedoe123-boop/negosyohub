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
                    ->formatStateUsing(fn (StoreStatus $state): string => $state->name)
                    ->color(fn (StoreStatus $state): string => match ($state) {
                        StoreStatus::Approved => 'success',
                        StoreStatus::Pending => 'warning',
                        StoreStatus::Rejected => 'danger',
                        StoreStatus::Suspended => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registered')
                    ->since(),
            ])
            ->paginated(false);
    }
}
