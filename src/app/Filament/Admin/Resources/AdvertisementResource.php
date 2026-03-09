<?php

namespace App\Filament\Admin\Resources;

use App\AdPlacement;
use App\AdStatus;
use App\Filament\Admin\Resources\AdvertisementResource\Pages;
use App\Models\Advertisement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class AdvertisementResource extends Resource
{
    protected static ?string $model = Advertisement::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationGroup = 'Marketing';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Ad Details')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('placement')
                            ->options(collect(AdPlacement::cases())
                                ->mapWithKeys(fn (AdPlacement $p): array => [$p->value => $p->label()]))
                            ->required()
                            ->native(false),
                        Forms\Components\RichEditor::make('description')
                            ->maxLength(10000)
                            ->columnSpanFull(),
                    ])->columns(2),
                Forms\Components\Section::make('Creative & Link')
                    ->schema([
                        Forms\Components\TextInput::make('image_url')
                            ->label('Image URL')
                            ->url()
                            ->maxLength(2048)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('link_url')
                            ->label('Destination URL')
                            ->url()
                            ->maxLength(2048)
                            ->columnSpanFull(),
                    ]),
                Forms\Components\Section::make('Settings')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options(collect(AdStatus::cases())
                                ->mapWithKeys(fn (AdStatus $s): array => [$s->value => $s->label()]))
                            ->required()
                            ->default(AdStatus::Draft->value)
                            ->native(false),
                        Forms\Components\TextInput::make('priority')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->maxValue(100),
                        Forms\Components\TextInput::make('cost_cents')
                            ->label('Cost (cents)')
                            ->numeric()
                            ->minValue(0),
                        Forms\Components\Select::make('campaign_id')
                            ->label('Campaign')
                            ->relationship('campaign', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                    ])->columns(2),
                Forms\Components\Section::make('Schedule')
                    ->schema([
                        Forms\Components\DateTimePicker::make('starts_at')
                            ->label('Start Date'),
                        Forms\Components\DateTimePicker::make('ends_at')
                            ->label('End Date')
                            ->afterOrEqual('starts_at'),
                        Forms\Components\Hidden::make('created_by')
                            ->default(fn () => Auth::id()),
                    ])->columns(2),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Advertisement')
                    ->schema([
                        Infolists\Components\TextEntry::make('title')
                            ->weight('bold')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large),
                        Infolists\Components\TextEntry::make('placement')
                            ->badge()
                            ->formatStateUsing(fn ($state): string => $state instanceof AdPlacement ? $state->label() : ucfirst((string) $state))
                            ->color('primary'),
                        Infolists\Components\TextEntry::make('description')
                            ->html()
                            ->columnSpanFull(),
                    ])->columns(2),
                Infolists\Components\Section::make('Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->formatStateUsing(fn ($state): string => $state instanceof AdStatus ? $state->label() : ucfirst((string) $state))
                            ->color(fn ($state): string => $state instanceof AdStatus ? $state->color() : 'gray'),
                        Infolists\Components\TextEntry::make('priority'),
                        Infolists\Components\TextEntry::make('cost_cents')
                            ->label('Cost')
                            ->formatStateUsing(fn (?int $state): string => $state ? '₱'.number_format($state / 100, 2) : '—'),
                        Infolists\Components\TextEntry::make('campaign.name')
                            ->label('Campaign')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('author.name')
                            ->label('Created By')
                            ->default('System'),
                        Infolists\Components\TextEntry::make('image_url')
                            ->label('Image')
                            ->url(fn ($state): ?string => $state)
                            ->placeholder('—')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('link_url')
                            ->label('Link')
                            ->url(fn ($state): ?string => $state)
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ])->columns(3),
                Infolists\Components\Section::make('Schedule')
                    ->schema([
                        Infolists\Components\TextEntry::make('starts_at')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('ends_at')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('created_at')
                            ->dateTime(),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(40),
                Tables\Columns\TextColumn::make('placement')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof AdPlacement ? $state->label() : ucfirst((string) $state))
                    ->color('primary')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof AdStatus ? $state->label() : ucfirst((string) $state))
                    ->color(fn ($state): string => $state instanceof AdStatus ? $state->color() : 'gray')
                    ->sortable(),
                Tables\Columns\TextColumn::make('priority')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('cost_cents')
                    ->label('Cost')
                    ->formatStateUsing(fn (?int $state): string => $state ? '₱'.number_format($state / 100, 2) : '—')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('campaign.name')
                    ->label('Campaign')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Starts')
                    ->dateTime('M d, Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('ends_at')
                    ->label('Ends')
                    ->dateTime('M d, Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('placement')
                    ->options(collect(AdPlacement::cases())
                        ->mapWithKeys(fn (AdPlacement $p): array => [$p->value => $p->label()])),
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(AdStatus::cases())
                        ->mapWithKeys(fn (AdStatus $s): array => [$s->value => $s->label()])),
                Tables\Filters\SelectFilter::make('campaign_id')
                    ->label('Campaign')
                    ->relationship('campaign', 'name')
                    ->searchable()
                    ->preload(),
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
            ->defaultSort('priority', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdvertisements::route('/'),
            'create' => Pages\CreateAdvertisement::route('/create'),
            'view' => Pages\ViewAdvertisement::route('/{record}'),
            'edit' => Pages\EditAdvertisement::route('/{record}/edit'),
        ];
    }
}
