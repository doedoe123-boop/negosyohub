<?php

namespace App\Filament\Admin\Resources;

use App\DeliveryStatus;
use App\Filament\Admin\Resources\OrderResource\Pages;
use App\Models\Order;
use App\Models\Store;
use App\OrderPaymentMethod;
use App\OrderPaymentStatus;
use App\OrderStatus;
use App\Services\LogisticsManager;
use App\Services\OrderService;
use App\ShipmentProvider;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
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
                        Infolists\Components\TextEntry::make('payment_method')
                            ->label('Payment Method')
                            ->badge()
                            ->formatStateUsing(function (OrderPaymentMethod|string|null $state): string {
                                if ($state instanceof OrderPaymentMethod) {
                                    return $state->label();
                                }

                                return $state ? (OrderPaymentMethod::tryFrom($state)?->label() ?? ucfirst(str_replace('_', ' ', $state))) : '—';
                            }),
                        Infolists\Components\TextEntry::make('payment_status')
                            ->label('Payment Status')
                            ->badge()
                            ->formatStateUsing(function (OrderPaymentStatus|string|null $state): string {
                                if ($state instanceof OrderPaymentStatus) {
                                    return $state->label();
                                }

                                return $state ? (OrderPaymentStatus::tryFrom($state)?->label() ?? ucfirst($state)) : '—';
                            })
                            ->color(function (OrderPaymentStatus|string|null $state): string {
                                if ($state instanceof OrderPaymentStatus) {
                                    return $state->color();
                                }

                                return $state ? (OrderPaymentStatus::tryFrom($state)?->color() ?? 'gray') : 'gray';
                            }),
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
                Infolists\Components\Section::make('Shipment')
                    ->schema([
                        Infolists\Components\TextEntry::make('latestShipment.provider')
                            ->label('Provider')
                            ->formatStateUsing(fn ($state): string => $state instanceof ShipmentProvider ? $state->label() : ($state ? (ShipmentProvider::tryFrom((string) $state)?->label() ?? (string) $state) : '—')),
                        Infolists\Components\TextEntry::make('latestShipment.delivery_status')
                            ->label('Delivery Status')
                            ->badge()
                            ->formatStateUsing(fn ($state): string => $state instanceof DeliveryStatus ? $state->label() : ($state ? (DeliveryStatus::tryFrom((string) $state)?->label() ?? (string) $state) : '—'))
                            ->color(fn ($state): string => $state instanceof DeliveryStatus ? $state->color() : (DeliveryStatus::tryFrom((string) $state)?->color() ?? 'gray')),
                        Infolists\Components\TextEntry::make('latestShipment.driver_name')
                            ->label('Driver')
                            ->default('—'),
                        Infolists\Components\TextEntry::make('latestShipment.driver_contact')
                            ->label('Driver Contact')
                            ->default('—'),
                        Infolists\Components\TextEntry::make('latestShipment.tracking_url')
                            ->label('Tracking URL')
                            ->url(fn (Order $record): ?string => $record->latestShipment?->tracking_url)
                            ->openUrlInNewTab()
                            ->default('—'),
                    ])->columns(2)
                    ->collapsed(),

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
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Payment')
                    ->badge()
                    ->formatStateUsing(function (OrderPaymentMethod|string|null $state): string {
                        if ($state instanceof OrderPaymentMethod) {
                            return $state->label();
                        }

                        return $state ? (OrderPaymentMethod::tryFrom($state)?->label() ?? ucfirst(str_replace('_', ' ', $state))) : '—';
                    }),
                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Payment Status')
                    ->badge()
                    ->formatStateUsing(function (OrderPaymentStatus|string|null $state): string {
                        if ($state instanceof OrderPaymentStatus) {
                            return $state->label();
                        }

                        return $state ? (OrderPaymentStatus::tryFrom($state)?->label() ?? ucfirst($state)) : '—';
                    })
                    ->color(function (OrderPaymentStatus|string|null $state): string {
                        if ($state instanceof OrderPaymentStatus) {
                            return $state->color();
                        }

                        return $state ? (OrderPaymentStatus::tryFrom($state)?->color() ?? 'gray') : 'gray';
                    }),
                Tables\Columns\TextColumn::make('latestShipment.delivery_status')
                    ->label('Delivery')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof DeliveryStatus ? $state->label() : ($state ? (DeliveryStatus::tryFrom((string) $state)?->label() ?? (string) $state) : '—'))
                    ->color(fn ($state): string => $state instanceof DeliveryStatus ? $state->color() : (DeliveryStatus::tryFrom((string) $state)?->color() ?? 'gray')),
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
                        DatePicker::make('from')->label('Placed From'),
                        DatePicker::make('until')->label('Placed Until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
                            ->when($data['until'], fn ($q, $v) => $q->whereDate('created_at', '<=', $v));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('manageShipment')
                    ->label('Manage Shipment')
                    ->icon('heroicon-o-truck')
                    ->color('gray')
                    ->form([
                        Forms\Components\Select::make('provider')
                            ->options(collect(ShipmentProvider::cases())->mapWithKeys(fn (ShipmentProvider $provider): array => [$provider->value => $provider->label()]))
                            ->default(ShipmentProvider::Manual->value),
                        Forms\Components\TextInput::make('external_reference')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('driver_name')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('driver_contact')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('vehicle_type')
                            ->maxLength(100),
                        Forms\Components\TextInput::make('tracking_url')
                            ->url(),
                    ])
                    ->fillForm(fn (Order $record): array => [
                        'provider' => $record->latestShipment?->provider?->value ?? ShipmentProvider::Manual->value,
                        'external_reference' => $record->latestShipment?->external_reference,
                        'driver_name' => $record->latestShipment?->driver_name,
                        'driver_contact' => $record->latestShipment?->driver_contact,
                        'vehicle_type' => $record->latestShipment?->vehicle_type,
                        'tracking_url' => $record->latestShipment?->tracking_url,
                    ])
                    ->action(function (Order $record, array $data): void {
                        app(LogisticsManager::class)->upsertShipment($record->loadMissing('store', 'addresses'), $data);

                        Notification::make()
                            ->title('Shipment saved')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('updateDeliveryStatus')
                    ->label('Delivery Status')
                    ->icon('heroicon-o-map')
                    ->color('info')
                    ->visible(fn (Order $record): bool => $record->latestShipment !== null)
                    ->form([
                        Forms\Components\Select::make('delivery_status')
                            ->required()
                            ->options(collect(DeliveryStatus::cases())->mapWithKeys(fn (DeliveryStatus $status): array => [$status->value => $status->label()])),
                        Forms\Components\TextInput::make('driver_name')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('driver_contact')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('vehicle_type')
                            ->maxLength(100),
                        Forms\Components\TextInput::make('tracking_url')
                            ->url(),
                    ])
                    ->fillForm(fn (Order $record): array => [
                        'delivery_status' => $record->latestShipment?->delivery_status?->value,
                        'driver_name' => $record->latestShipment?->driver_name,
                        'driver_contact' => $record->latestShipment?->driver_contact,
                        'vehicle_type' => $record->latestShipment?->vehicle_type,
                        'tracking_url' => $record->latestShipment?->tracking_url,
                    ])
                    ->action(function (Order $record, array $data): void {
                        $shipment = $record->latestShipment;

                        if (! $shipment) {
                            return;
                        }

                        app(LogisticsManager::class)->updateStatus(
                            $shipment,
                            DeliveryStatus::from($data['delivery_status']),
                            $data
                        );

                        Notification::make()
                            ->title('Shipment updated')
                            ->success()
                            ->send();
                    }),
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
                Tables\Actions\Action::make('markShipped')
                    ->label('Shipped')
                    ->icon('heroicon-o-truck')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Order $record): bool => $record->status === OrderStatus::Preparing->value)
                    ->action(function (Order $record): void {
                        static::transitionOrder($record, 'markShipped');
                    }),
                Tables\Actions\Action::make('markDelivered')
                    ->label('Delivered')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Order $record): bool => $record->status === OrderStatus::Shipped->value)
                    ->action(function (Order $record): void {
                        static::transitionOrder($record, 'markDelivered');
                    }),
                Tables\Actions\Action::make('markPaid')
                    ->label('Mark as Paid')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Order $record): bool => $record->payment_method === OrderPaymentMethod::CashOnDelivery && $record->payment_status === OrderPaymentStatus::Unpaid)
                    ->action(function (Order $record): void {
                        static::transitionOrder($record, 'markPaid');
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
