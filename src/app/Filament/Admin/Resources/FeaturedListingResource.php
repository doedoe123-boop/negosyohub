<?php

namespace App\Filament\Admin\Resources;

use App\AdStatus;
use App\FeaturedType;
use App\Filament\Admin\Resources\FeaturedListingResource\Pages;
use App\Models\FeaturedListing;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class FeaturedListingResource extends Resource
{
    protected static ?string $model = FeaturedListing::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static ?string $navigationGroup = 'Marketing';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Featured Listings';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Featured Listing Details')
                    ->schema([
                        Forms\Components\Select::make('featured_type')
                            ->label('Type')
                            ->options(collect(FeaturedType::cases())
                                ->mapWithKeys(fn (FeaturedType $t): array => [$t->value => $t->label()]))
                            ->required()
                            ->native(false),
                        Forms\Components\Select::make('status')
                            ->options(collect(AdStatus::cases())
                                ->mapWithKeys(fn (AdStatus $s): array => [$s->value => $s->label()]))
                            ->required()
                            ->default(AdStatus::Draft->value)
                            ->native(false),
                        Forms\Components\MorphToSelect::make('featurable')
                            ->label('Featured Item')
                            ->types([
                                Forms\Components\MorphToSelect\Type::make(\App\Models\Store::class)
                                    ->titleAttribute('name'),
                            ])
                            ->searchable()
                            ->preload()
                            ->columnSpanFull(),
                    ])->columns(2),
                Forms\Components\Section::make('Settings')
                    ->schema([
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
                    ])->columns(3),
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
                Infolists\Components\Section::make('Featured Listing')
                    ->schema([
                        Infolists\Components\TextEntry::make('featured_type')
                            ->badge()
                            ->formatStateUsing(fn ($state): string => $state instanceof FeaturedType ? $state->label() : ucfirst((string) $state))
                            ->color('warning'),
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->formatStateUsing(fn ($state): string => $state instanceof AdStatus ? $state->label() : ucfirst((string) $state))
                            ->color(fn ($state): string => $state instanceof AdStatus ? $state->color() : 'gray'),
                        Infolists\Components\TextEntry::make('featurable_type')
                            ->label('Item Type')
                            ->formatStateUsing(fn (string $state): string => class_basename($state)),
                        Infolists\Components\TextEntry::make('featurable_id')
                            ->label('Item ID'),
                    ])->columns(4),
                Infolists\Components\Section::make('Details')
                    ->schema([
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
                    ])->columns(4),
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
                Tables\Columns\TextColumn::make('featured_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof FeaturedType ? $state->label() : ucfirst((string) $state))
                    ->color('warning')
                    ->sortable(),
                Tables\Columns\TextColumn::make('featurable_type')
                    ->label('Item Type')
                    ->formatStateUsing(fn (string $state): string => class_basename($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof AdStatus ? $state->label() : ucfirst((string) $state))
                    ->color(fn ($state): string => $state instanceof AdStatus ? $state->color() : 'gray')
                    ->sortable(),
                Tables\Columns\TextColumn::make('priority')
                    ->sortable(),
                Tables\Columns\TextColumn::make('cost_cents')
                    ->label('Cost')
                    ->formatStateUsing(fn (?int $state): string => $state ? '₱'.number_format($state / 100, 2) : '—')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('campaign.name')
                    ->label('Campaign')
                    ->placeholder('—')
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
                Tables\Filters\SelectFilter::make('featured_type')
                    ->label('Type')
                    ->options(collect(FeaturedType::cases())
                        ->mapWithKeys(fn (FeaturedType $t): array => [$t->value => $t->label()])),
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(AdStatus::cases())
                        ->mapWithKeys(fn (AdStatus $s): array => [$s->value => $s->label()])),
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
            'index' => Pages\ListFeaturedListings::route('/'),
            'create' => Pages\CreateFeaturedListing::route('/create'),
            'view' => Pages\ViewFeaturedListing::route('/{record}'),
            'edit' => Pages\EditFeaturedListing::route('/{record}/edit'),
        ];
    }
}
