<?php

namespace App\Filament\Admin\Resources\StoreResource\RelationManagers;

use App\Models\Order;
use App\OrderStatus;
use App\Services\OrderService;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    protected static ?string $title = 'Orders';

    protected static ?string $icon = 'heroicon-o-shopping-bag';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Order #')
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->default('—'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => OrderStatus::tryFrom($state)?->label() ?? ucfirst($state))
                    ->color(fn (string $state): string => OrderStatus::tryFrom($state)?->color() ?? 'gray'),
                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->getStateUsing(fn (Order $record): string => '₱'.number_format(($record->total?->value ?? 0) / 100, 2)),
                Tables\Columns\TextColumn::make('commission_amount')
                    ->label('Commission')
                    ->getStateUsing(fn (Order $record): string => '₱'.number_format(($record->commission_amount?->value ?? 0) / 100, 2)),
                Tables\Columns\TextColumn::make('store_earning')
                    ->label('Store Earning')
                    ->getStateUsing(fn (Order $record): string => '₱'.number_format(($record->store_earning?->value ?? 0) / 100, 2)),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Placed At')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(
                        collect(OrderStatus::cases())->mapWithKeys(
                            fn (OrderStatus $s) => [$s->value => $s->label()]
                        )
                    ),
            ])
            ->actions([
                Tables\Actions\Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Order $record): bool => in_array($record->status, array_map(fn (OrderStatus $s) => $s->value, OrderStatus::active()), true))
                    ->action(function (Order $record): void {
                        try {
                            app(OrderService::class)->cancel($record);
                            Notification::make()->title('Order cancelled')->success()->send();
                        } catch (ValidationException $e) {
                            Notification::make()
                                ->title('Cannot cancel order')
                                ->body(collect($e->errors())->flatten()->first())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
