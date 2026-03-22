<?php

namespace App\Filament\Resources;

use App\Models\Order;
use App\Models\User;
use App\OrderPaymentMethod;
use App\OrderPaymentStatus;
use App\OrderStatus;
use App\Services\OrderService;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Lunar\Admin\Filament\Resources\OrderResource as LunarOrderResource;

class ScopedOrderResource extends LunarOrderResource
{
    protected static ?string $slug = 'orders';

    /**
     * Scope orders to the authenticated store owner's store.
     *
     * Admins see all orders; store owners see only their store's orders.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        /** @var User|null $user */
        $user = Auth::user();

        if ($user && ! $user->isAdmin()) {
            $store = $user->getStoreForPanel();
            $query->where('store_id', $store?->id);
        }

        return $query;
    }

    public static function table(Table $table): Table
    {
        return parent::table($table)
            ->actions([
                Tables\Actions\Action::make('confirm')
                    ->label('Confirm')
                    ->icon('heroicon-o-check')
                    ->color('info')
                    ->requiresConfirmation()
                    ->visible(fn (Order $record): bool => $record->status === OrderStatus::Pending->value)
                    ->action(fn (Order $record) => static::transitionOrder($record, 'confirm')),
                Tables\Actions\Action::make('markPreparing')
                    ->label('Preparing')
                    ->icon('heroicon-o-fire')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->visible(fn (Order $record): bool => $record->status === OrderStatus::Confirmed->value)
                    ->action(fn (Order $record) => static::transitionOrder($record, 'markPreparing')),
                Tables\Actions\Action::make('markShipped')
                    ->label('Shipped')
                    ->icon('heroicon-o-truck')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Order $record): bool => $record->status === OrderStatus::Preparing->value)
                    ->action(fn (Order $record) => static::transitionOrder($record, 'markShipped')),
                Tables\Actions\Action::make('markDelivered')
                    ->label('Delivered')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Order $record): bool => $record->status === OrderStatus::Shipped->value)
                    ->action(fn (Order $record) => static::transitionOrder($record, 'markDelivered')),
                Tables\Actions\Action::make('markPaid')
                    ->label('Mark as Paid')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Order $record): bool => $record->payment_method === OrderPaymentMethod::CashOnDelivery && $record->payment_status === OrderPaymentStatus::Unpaid)
                    ->action(fn (Order $record) => static::transitionOrder($record, 'markPaid')),
                Tables\Actions\Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Order $record): bool => in_array(
                        $record->status,
                        array_map(fn (OrderStatus $s) => $s->value, OrderStatus::active()),
                        true,
                    ))
                    ->action(fn (Order $record) => static::transitionOrder($record, 'cancel')),
            ]);
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
