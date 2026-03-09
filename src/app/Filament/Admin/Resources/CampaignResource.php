<?php

namespace App\Filament\Admin\Resources;

use App\CampaignStatus;
use App\Filament\Admin\Resources\CampaignResource\Pages;
use App\Filament\Admin\Resources\CampaignResource\RelationManagers;
use App\Models\Campaign;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class CampaignResource extends Resource
{
    protected static ?string $model = Campaign::class;

    protected static ?string $navigationIcon = 'heroicon-o-rocket-launch';

    protected static ?string $navigationGroup = 'Marketing';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Campaign Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('status')
                            ->options(collect(CampaignStatus::cases())
                                ->mapWithKeys(fn (CampaignStatus $s): array => [$s->value => $s->label()]))
                            ->required()
                            ->default(CampaignStatus::Draft->value)
                            ->native(false),
                        Forms\Components\RichEditor::make('description')
                            ->maxLength(10000)
                            ->columnSpanFull(),
                    ])->columns(2),
                Forms\Components\Section::make('Schedule')
                    ->schema([
                        Forms\Components\DateTimePicker::make('starts_at')
                            ->label('Start Date')
                            ->required(),
                        Forms\Components\DateTimePicker::make('ends_at')
                            ->label('End Date')
                            ->afterOrEqual('starts_at')
                            ->required(),
                        Forms\Components\Hidden::make('created_by')
                            ->default(fn () => Auth::id()),
                    ])->columns(2),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Campaign')
                    ->schema([
                        Infolists\Components\TextEntry::make('name')
                            ->weight('bold')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('description')
                            ->html()
                            ->columnSpanFull(),
                    ]),
                Infolists\Components\Section::make('Status & Schedule')
                    ->schema([
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->formatStateUsing(fn ($state): string => $state instanceof CampaignStatus ? $state->label() : ucfirst((string) $state))
                            ->color(fn ($state): string => $state instanceof CampaignStatus ? $state->color() : 'gray'),
                        Infolists\Components\TextEntry::make('author.name')
                            ->label('Created By')
                            ->default('System'),
                        Infolists\Components\TextEntry::make('starts_at')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('ends_at')
                            ->dateTime(),
                    ])->columns(4),
                Infolists\Components\Section::make('Summary')
                    ->schema([
                        Infolists\Components\TextEntry::make('advertisements_count')
                            ->label('Advertisements')
                            ->state(fn (Campaign $record): int => $record->advertisements()->count()),
                        Infolists\Components\TextEntry::make('promotions_count')
                            ->label('Promotions')
                            ->state(fn (Campaign $record): int => $record->promotions()->count()),
                        Infolists\Components\TextEntry::make('coupons_count')
                            ->label('Coupons')
                            ->state(fn (Campaign $record): int => $record->coupons()->count()),
                        Infolists\Components\TextEntry::make('featured_listings_count')
                            ->label('Featured Listings')
                            ->state(fn (Campaign $record): int => $record->featuredListings()->count()),
                    ])->columns(4),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof CampaignStatus ? $state->label() : ucfirst((string) $state))
                    ->color(fn ($state): string => $state instanceof CampaignStatus ? $state->color() : 'gray')
                    ->sortable(),
                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Starts')
                    ->dateTime('M d, Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('ends_at')
                    ->label('Ends')
                    ->dateTime('M d, Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('advertisements_count')
                    ->label('Ads')
                    ->counts('advertisements')
                    ->sortable(),
                Tables\Columns\TextColumn::make('promotions_count')
                    ->label('Promos')
                    ->counts('promotions')
                    ->sortable(),
                Tables\Columns\TextColumn::make('coupons_count')
                    ->label('Coupons')
                    ->counts('coupons')
                    ->sortable(),
                Tables\Columns\TextColumn::make('author.name')
                    ->label('By')
                    ->placeholder('System')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(CampaignStatus::cases())
                        ->mapWithKeys(fn (CampaignStatus $s): array => [$s->value => $s->label()])),
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
            RelationManagers\AdvertisementsRelationManager::class,
            RelationManagers\PromotionsRelationManager::class,
            RelationManagers\CouponsRelationManager::class,
            RelationManagers\FeaturedListingsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCampaigns::route('/'),
            'create' => Pages\CreateCampaign::route('/create'),
            'view' => Pages\ViewCampaign::route('/{record}'),
            'edit' => Pages\EditCampaign::route('/{record}/edit'),
        ];
    }
}
