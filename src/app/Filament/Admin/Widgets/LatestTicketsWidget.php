<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Resources\SupportTicketResource;
use App\Models\SupportTicket;
use App\TicketCategory;
use App\TicketPriority;
use App\TicketStatus;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestTicketsWidget extends TableWidget
{
    protected static ?string $heading = 'Recent Support Tickets';

    protected static ?int $sort = 11;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                SupportTicket::query()
                    ->with(['user', 'store'])
                    ->latest()
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#'),
                Tables\Columns\TextColumn::make('subject')
                    ->limit(40),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('From'),
                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->formatStateUsing(fn (TicketCategory $state): string => $state->label())
                    ->color('gray'),
                Tables\Columns\TextColumn::make('priority')
                    ->badge()
                    ->formatStateUsing(fn (TicketPriority $state): string => ucfirst($state->value))
                    ->color(fn (TicketPriority $state): string => match ($state) {
                        TicketPriority::Low => 'gray',
                        TicketPriority::Medium => 'info',
                        TicketPriority::High => 'warning',
                        TicketPriority::Urgent => 'danger',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (TicketStatus $state): string => str_replace('_', ' ', ucfirst($state->value)))
                    ->color(fn (TicketStatus $state): string => match ($state) {
                        TicketStatus::Open => 'warning',
                        TicketStatus::InProgress => 'info',
                        TicketStatus::Resolved => 'success',
                        TicketStatus::Closed => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Opened')
                    ->since(),
            ])
            ->paginated(false)
            ->actions([
                Tables\Actions\Action::make('view')
                    ->url(fn (SupportTicket $record): string => SupportTicketResource::getUrl('view', ['record' => $record]))
                    ->icon('heroicon-o-eye'),
            ]);
    }
}
