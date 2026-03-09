<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Resources\OrderResource;
use App\Models\Order;
use App\OrderStatus;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestOrdersWidget extends TableWidget
{
    protected static ?string $heading = 'Recent Orders';

    protected static ?int $sort = 10;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Order::query()
                    ->with(['store', 'customer'])
                    ->latest('placed_at')
                    ->limit(8)
            )
            ->columns([
                Tables\Columns\TextColumn::make('identifier')
                    ->label('Order #')
                    ->searchable(),

                Tables\Columns\TextColumn::make('store.name')
                    ->label('Store')
                    ->limit(24),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->limit(24),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => OrderStatus::tryFrom($state)?->label() ?? ucfirst($state))
                    ->color(fn (string $state): string => OrderStatus::tryFrom($state)?->color() ?? 'gray'),

                Tables\Columns\TextColumn::make('placed_at')
                    ->label('Placed')
                    ->since()
                    ->sortable(),
            ])
            ->paginated(false)
            ->actions([
                Tables\Actions\Action::make('view')
                    ->url(fn (Order $record): string => OrderResource::getUrl('view', ['record' => $record]))
                    ->icon('heroicon-o-eye'),
            ]);
    }
}
