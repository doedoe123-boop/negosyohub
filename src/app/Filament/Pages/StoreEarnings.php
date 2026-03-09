<?php

namespace App\Filament\Pages;

use App\Models\Order;
use App\Models\Payout;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StoreEarnings extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Earnings & Payouts';

    protected static string $view = 'filament.pages.store-earnings';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $store = auth()->user()->getStoreForPanel();

        if (! $store) {
            return ['stats' => []];
        }

        $totalEarnings = Order::query()->forStore($store->id)
            ->delivered()
            ->sum('store_earning') / 100;

        $totalCommission = Order::query()->forStore($store->id)
            ->delivered()
            ->sum('commission_amount') / 100;

        $pendingOrders = Order::query()->forStore($store->id)
            ->active()
            ->sum('store_earning') / 100;

        $totalPaidOut = Payout::forStore($store->id)
            ->paid()
            ->sum('amount');

        $pendingPayouts = Payout::forStore($store->id)
            ->pending()
            ->sum('amount');

        return [
            'stats' => [
                Stat::make('Total Earnings', '₱'.number_format($totalEarnings, 2))
                    ->description('From delivered orders')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('success'),

                Stat::make('Commission Paid', '₱'.number_format($totalCommission, 2))
                    ->description('Platform commission')
                    ->icon('heroicon-o-receipt-percent')
                    ->color('warning'),

                Stat::make('Incoming Revenue', '₱'.number_format($pendingOrders, 2))
                    ->description('Active orders in progress')
                    ->icon('heroicon-o-clock')
                    ->color('info'),

                Stat::make('Paid Out', '₱'.number_format($totalPaidOut, 2))
                    ->description('₱'.number_format($pendingPayouts, 2).' pending')
                    ->icon('heroicon-o-banknotes')
                    ->color('success'),
            ],
        ];
    }

    public function table(Table $table): Table
    {
        $store = auth()->user()->getStoreForPanel();

        return $table
            ->query(
                Payout::query()
                    ->withCount('lines')
                    ->when($store, fn ($q) => $q->forStore($store->id))
                    ->latest('period_end')
            )
            ->columns([
                Tables\Columns\TextColumn::make('period_start')
                    ->label('Period')
                    ->date('M d, Y')
                    ->description(fn (Payout $record): string => 'to '.$record->period_end->format('M d, Y'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->money('PHP')
                    ->sortable(),

                Tables\Columns\TextColumn::make('lines_count')
                    ->label('Orders')
                    ->counts('lines')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Payout::STATUS_PAID => 'success',
                        Payout::STATUS_PROCESSING => 'info',
                        Payout::STATUS_PENDING => 'warning',
                        Payout::STATUS_FAILED => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('reference')
                    ->label('Reference')
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Paid At')
                    ->dateTime('M d, Y h:i A')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        Payout::STATUS_PENDING => 'Pending',
                        Payout::STATUS_PROCESSING => 'Processing',
                        Payout::STATUS_PAID => 'Paid',
                        Payout::STATUS_FAILED => 'Failed',
                    ]),
            ])
            ->emptyStateHeading('No payouts yet')
            ->emptyStateDescription('Payouts will appear here once the platform processes your earnings.')
            ->emptyStateIcon('heroicon-o-banknotes');
    }
}
