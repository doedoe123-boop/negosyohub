<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\OrderResource\Pages;
use App\Models\Order;
use App\Models\Store;
use App\OrderStatus;
use App\Services\OrderService;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationGroup = 'Marketplace';

    protected static ?int $navigationSort = 4;

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Order Summary')
                    ->schema([
                        Infolists\Components\TextEntry::make('id')
                            ->label('Order #'),
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => OrderStatus::tryFrom($state)?->label() ?? ucfirst($state))
                            ->color(fn (string $state): string => OrderStatus::tryFrom($state)?->color() ?? 'gray'),
                        Infolists\Components\TextEntry::make('store.name')
                            ->label('Store')
                            ->default('—'),
                        Infolists\Components\TextEntry::make('customer.name')
                            ->label('Customer')
                            ->default('—'),
                        Infolists\Components\TextEntry::make('total')
                            ->label('Order Total')
                            ->getStateUsing(fn (Order $record): string => '₱'.number_format(($record->total?->value ?? 0) / 100, 2)),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Placed At')
                            ->dateTime(),
                    ])->columns(2),

                Infolists\Components\Section::make('Commission Breakdown')
                    ->schema([
                        Infolists\Components\TextEntry::make('commission_amount')
                            ->label('Commission (Platform)')
                            ->getStateUsing(fn (Order $record): string => '₱'.number_format(($record->commission_amount?->value ?? 0) / 100, 2)),
                        Infolists\Components\TextEntry::make('store_earning')
                            ->label('Store Earning')
                            ->getStateUsing(fn (Order $record): string => '₱'.number_format(($record->store_earning?->value ?? 0) / 100, 2)),
                        Infolists\Components\TextEntry::make('platform_earning')
                            ->label('Platform Earning')
                            ->getStateUsing(fn (Order $record): string => '₱'.number_format(($record->platform_earning?->value ?? 0) / 100, 2)),
                    ])->columns(3),

                Infolists\Components\Section::make('Shipping Address')
                    ->schema([
                        Infolists\Components\TextEntry::make('shippingAddress.first_name')
                            ->label('First Name')
                            ->default('—'),
                        Infolists\Components\TextEntry::make('shippingAddress.last_name')
                            ->label('Last Name')
                            ->default('—'),
                        Infolists\Components\TextEntry::make('shippingAddress.line_one')
                            ->label('Address')
                            ->default('—'),
                        Infolists\Components\TextEntry::make('shippingAddress.city')
                            ->label('City')
                            ->default('—'),
                    ])->columns(2)
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Order #')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('store.name')
                    ->label('Store')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->default('—'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => OrderStatus::tryFrom($state)?->label() ?? ucfirst($state))
                    ->color(fn (string $state): string => OrderStatus::tryFrom($state)?->color() ?? 'gray')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->getStateUsing(fn (Order $record): string => '₱'.number_format(($record->total?->value ?? 0) / 100, 2))
                    ->sortable(),
                Tables\Columns\TextColumn::make('commission_amount')
                    ->label('Commission')
                    ->getStateUsing(fn (Order $record): string => '₱'.number_format(($record->commission_amount?->value ?? 0) / 100, 2))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('store_earning')
                    ->label('Store Earning')
                    ->getStateUsing(fn (Order $record): string => '₱'.number_format(($record->store_earning?->value ?? 0) / 100, 2))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Placed At')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('store_id')
                    ->label('Store')
                    ->options(Store::query()->pluck('name', 'id'))
                    ->searchable(),
                Tables\Filters\SelectFilter::make('status')
                    ->options(
                        collect(OrderStatus::cases())->mapWithKeys(
                            fn (OrderStatus $s) => [$s->value => $s->label()]
                        )
                    ),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')->label('Placed From'),
                        \Filament\Forms\Components\DatePicker::make('until')->label('Placed Until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
                            ->when($data['until'], fn ($q, $v) => $q->whereDate('created_at', '<=', $v));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('confirm')
                    ->label('Confirm')
                    ->icon('heroicon-o-check')
                    ->color('info')
                    ->requiresConfirmation()
                    ->visible(fn (Order $record): bool => $record->status === OrderStatus::Pending->value)
                    ->action(function (Order $record): void {
                        static::transitionOrder($record, 'confirm');
                    }),
                Tables\Actions\Action::make('markPreparing')
                    ->label('Preparing')
                    ->icon('heroicon-o-fire')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->visible(fn (Order $record): bool => $record->status === OrderStatus::Confirmed->value)
                    ->action(function (Order $record): void {
                        static::transitionOrder($record, 'markPreparing');
                    }),
                Tables\Actions\Action::make('markReady')
                    ->label('Ready')
                    ->icon('heroicon-o-truck')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Order $record): bool => $record->status === OrderStatus::Preparing->value)
                    ->action(function (Order $record): void {
                        static::transitionOrder($record, 'markReady');
                    }),
                Tables\Actions\Action::make('markDelivered')
                    ->label('Delivered')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Order $record): bool => $record->status === OrderStatus::Ready->value)
                    ->action(function (Order $record): void {
                        static::transitionOrder($record, 'markDelivered');
                    }),
                Tables\Actions\Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Order $record): bool => in_array($record->status, array_map(fn (OrderStatus $s) => $s->value, OrderStatus::active()), true))
                    ->action(function (Order $record): void {
                        static::transitionOrder($record, 'cancel');
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'view' => Pages\ViewOrder::route('/{record}'),
        ];
    }

    /**
     * Run an OrderService transition and show a Filament notification.
     */
    private static function transitionOrder(Order $order, string $method): void
    {
        try {
            app(OrderService::class)->{$method}($order);

            Notification::make()
                ->title('Order updated')
                ->body("Order #{$order->id} status updated.")
                ->success()
                ->send();
        } catch (ValidationException $e) {
            Notification::make()
                ->title('Cannot update order')
                ->body(collect($e->errors())->flatten()->first())
                ->danger()
                ->send();
        }
    }
}
