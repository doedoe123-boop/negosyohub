<?php

namespace App\Filament\LipatBahay\Widgets;

use App\Models\MovingBooking;
use App\MovingBookingStatus;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentMovingBookingsTable extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 3;

    public function table(Table $table): Table
    {
        $store = auth()->user()?->getStoreForPanel();

        return $table
            ->query(
                MovingBooking::query()
                    ->when($store, fn ($query) => $query->where('store_id', $store->id))
                    ->latest('created_at')
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID'),
                Tables\Columns\TextColumn::make('contact_name')
                    ->label('Customer'),
                Tables\Columns\TextColumn::make('pickup_city')
                    ->label('From'),
                Tables\Columns\TextColumn::make('delivery_city')
                    ->label('To'),
                Tables\Columns\TextColumn::make('scheduled_at')
                    ->dateTime('M j, Y g:i A'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (MovingBookingStatus $state) => $state->color())
                    ->formatStateUsing(fn (MovingBookingStatus $state) => $state->label()),
                Tables\Columns\TextColumn::make('total_price')
                    ->label('Total')
                    ->formatStateUsing(fn (int $state) => '₱'.number_format($state / 100, 2)),
            ])
            ->paginated(false);
    }
}
