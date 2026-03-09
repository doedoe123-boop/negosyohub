<?php

namespace App\Filament\Admin\Widgets;

use App\Models\User;
use App\UserRole;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentCustomersWidget extends TableWidget
{
    protected static ?string $heading = 'Recent Customer Signups';

    protected static ?int $sort = 8;

    protected int|string|array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                User::query()
                    ->where('role', UserRole::Customer)
                    ->latest()
                    ->limit(6)
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->limit(20),

                Tables\Columns\TextColumn::make('email')
                    ->limit(24)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('orders_count')
                    ->label('Orders')
                    ->counts('orders'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Joined')
                    ->since(),
            ])
            ->paginated(false);
    }
}
