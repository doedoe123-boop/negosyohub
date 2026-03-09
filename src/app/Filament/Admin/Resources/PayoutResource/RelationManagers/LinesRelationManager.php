<?php

namespace App\Filament\Admin\Resources\PayoutResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class LinesRelationManager extends RelationManager
{
    protected static string $relationship = 'lines';

    protected static ?string $title = 'Order Breakdown';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('order_id')
            ->columns([
                Tables\Columns\TextColumn::make('order.identifier')
                    ->label('Order #')
                    ->searchable(),
                Tables\Columns\TextColumn::make('store_earning')
                    ->label('Store Earning')
                    ->formatStateUsing(fn (int $state): string => '₱'.number_format($state / 100, 2))
                    ->sortable(),
                Tables\Columns\TextColumn::make('order.placed_at')
                    ->label('Order Date')
                    ->dateTime('M d, Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Included At')
                    ->dateTime('M d, Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('order_id', 'desc')
            ->paginated([10, 25, 50]);
    }
}
