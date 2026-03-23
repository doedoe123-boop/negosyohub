<?php

namespace App\Filament\Resources\WebhookEndpointResource\RelationManagers;

use App\Models\WebhookDelivery;
use App\WebhookDeliveryStatus;
use Filament\Infolists;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class DeliveriesRelationManager extends RelationManager
{
    protected static string $relationship = 'deliveries';

    protected static ?string $title = 'Delivery Log';

    protected static ?string $icon = 'heroicon-o-paper-airplane';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('event')
            ->columns([
                Tables\Columns\TextColumn::make('event')
                    ->badge(),
                Tables\Columns\TextColumn::make('delivery_status')
                    ->badge()
                    ->formatStateUsing(fn (WebhookDeliveryStatus|string $state): string => $state instanceof WebhookDeliveryStatus ? $state->label() : (WebhookDeliveryStatus::tryFrom($state)?->label() ?? $state))
                    ->color(fn (WebhookDeliveryStatus|string $state): string => $state instanceof WebhookDeliveryStatus ? $state->color() : (WebhookDeliveryStatus::tryFrom($state)?->color() ?? 'gray')),
                Tables\Columns\TextColumn::make('response_status')
                    ->label('HTTP'),
                Tables\Columns\TextColumn::make('attempts'),
                Tables\Columns\TextColumn::make('created_at')
                    ->since()
                    ->label('Attempted'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->infolist([
                        Infolists\Components\Section::make('Delivery')
                            ->schema([
                                Infolists\Components\TextEntry::make('event'),
                                Infolists\Components\TextEntry::make('delivery_status')
                                    ->badge()
                                    ->formatStateUsing(fn (WebhookDeliveryStatus|string $state): string => $state instanceof WebhookDeliveryStatus ? $state->label() : (WebhookDeliveryStatus::tryFrom($state)?->label() ?? $state))
                                    ->color(fn (WebhookDeliveryStatus|string $state): string => $state instanceof WebhookDeliveryStatus ? $state->color() : (WebhookDeliveryStatus::tryFrom($state)?->color() ?? 'gray')),
                                Infolists\Components\TextEntry::make('response_status')
                                    ->label('HTTP Status')
                                    ->default('—'),
                                Infolists\Components\TextEntry::make('attempts'),
                                Infolists\Components\TextEntry::make('signature')
                                    ->default('—')
                                    ->copyable(),
                            ])->columns(2),
                        Infolists\Components\Section::make('Payload')
                            ->schema([
                                Infolists\Components\TextEntry::make('payload')
                                    ->formatStateUsing(fn (WebhookDelivery $record): string => json_encode($record->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}')
                                    ->columnSpanFull()
                                    ->copyable(),
                                Infolists\Components\TextEntry::make('response_body')
                                    ->label('Response Body')
                                    ->default('—')
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
