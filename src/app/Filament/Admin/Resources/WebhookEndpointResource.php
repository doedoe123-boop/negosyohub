<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\WebhookEndpointResource\Pages;
use App\Filament\Admin\Resources\WebhookEndpointResource\RelationManagers\DeliveriesRelationManager;
use App\Models\Store;
use App\Models\WebhookEndpoint;
use App\WebhookDeliveryStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WebhookEndpointResource extends Resource
{
    protected static ?string $model = WebhookEndpoint::class;

    protected static ?string $navigationIcon = 'heroicon-o-link';

    protected static ?string $navigationGroup = 'Integrations';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Endpoint')
                    ->schema([
                        Forms\Components\Select::make('store_id')
                            ->label('Store Scope')
                            ->helperText('Leave empty to make this a global endpoint.')
                            ->options(fn (): array => Store::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('url')
                            ->required()
                            ->url()
                            ->maxLength(2048)
                            ->placeholder('https://example.com/webhooks/orders'),
                        Forms\Components\TextInput::make('secret')
                            ->password()
                            ->revealable()
                            ->maxLength(255)
                            ->helperText('Used to sign the X-NegosyoHub-Signature header.'),
                        Forms\Components\CheckboxList::make('events')
                            ->options(WebhookEndpoint::eventOptions())
                            ->columns(2)
                            ->helperText('Leave empty to receive all supported events.')
                            ->columnSpanFull(),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->inline(false),
                    ])->columns(2),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Endpoint')
                    ->schema([
                        Infolists\Components\TextEntry::make('name'),
                        Infolists\Components\TextEntry::make('url')
                            ->url(fn (WebhookEndpoint $record): string => $record->url)
                            ->openUrlInNewTab(),
                        Infolists\Components\TextEntry::make('store.name')
                            ->label('Store Scope')
                            ->default('Global'),
                        Infolists\Components\IconEntry::make('is_active')
                            ->boolean()
                            ->label('Active'),
                        Infolists\Components\TextEntry::make('events')
                            ->badge()
                            ->state(fn (WebhookEndpoint $record): array => $record->events ?: ['all'])
                            ->formatStateUsing(function (string $state): string {
                                if ($state === 'all') {
                                    return 'All events';
                                }

                                return WebhookEndpoint::eventOptions()[$state] ?? $state;
                            })
                            ->separator(', '),
                        Infolists\Components\TextEntry::make('last_delivered_at')
                            ->label('Last Delivered')
                            ->since()
                            ->default('Never'),
                    ])->columns(2),
                Infolists\Components\Section::make('Recent Delivery Summary')
                    ->schema([
                        Infolists\Components\TextEntry::make('deliveries_count')
                            ->label('Total Deliveries')
                            ->state(fn (WebhookEndpoint $record): int => $record->deliveries()->count()),
                        Infolists\Components\TextEntry::make('successful_deliveries')
                            ->label('Delivered')
                            ->state(fn (WebhookEndpoint $record): int => $record->deliveries()->where('delivery_status', WebhookDeliveryStatus::Delivered->value)->count()),
                        Infolists\Components\TextEntry::make('failed_deliveries')
                            ->label('Failed')
                            ->state(fn (WebhookEndpoint $record): int => $record->deliveries()->where('delivery_status', WebhookDeliveryStatus::Failed->value)->count()),
                    ])->columns(3)
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('store.name')
                    ->label('Store Scope')
                    ->default('Global')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('url')
                    ->limit(40)
                    ->tooltip(fn (WebhookEndpoint $record): string => $record->url),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
                Tables\Columns\TextColumn::make('events')
                    ->badge()
                    ->state(fn (WebhookEndpoint $record): array => $record->events ?: ['all'])
                    ->formatStateUsing(function (string $state): string {
                        if ($state === 'all') {
                            return 'All events';
                        }

                        return WebhookEndpoint::eventOptions()[$state] ?? $state;
                    })
                    ->separator(', ')
                    ->limitList(2),
                Tables\Columns\TextColumn::make('last_delivered_at')
                    ->label('Last Delivered')
                    ->since()
                    ->default('Never')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
                Tables\Filters\SelectFilter::make('store_id')
                    ->label('Store Scope')
                    ->options(fn (): array => Store::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            DeliveriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWebhookEndpoints::route('/'),
            'create' => Pages\CreateWebhookEndpoint::route('/create'),
            'view' => Pages\ViewWebhookEndpoint::route('/{record}'),
            'edit' => Pages\EditWebhookEndpoint::route('/{record}/edit'),
        ];
    }
}
