<?php

namespace App\Filament\Admin\Resources;

use App\AdStatus;
use App\Filament\Admin\Resources\PromotionResource\Pages;
use App\Models\Promotion;
use App\PromotionType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class PromotionResource extends Resource
{
    protected static ?string $model = Promotion::class;

    protected static ?string $navigationIcon = 'heroicon-o-fire';

    protected static ?string $navigationGroup = 'Marketing';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Promotion Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('type')
                            ->options(collect(PromotionType::cases())
                                ->mapWithKeys(fn (PromotionType $t): array => [$t->value => $t->label()]))
                            ->required()
                            ->native(false),
                        Forms\Components\RichEditor::make('description')
                            ->maxLength(10000)
                            ->columnSpanFull(),
                    ])->columns(2),
                Forms\Components\Section::make('Discount')
                    ->schema([
                        Forms\Components\TextInput::make('discount_percentage')
                            ->label('Discount (%)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%'),
                        Forms\Components\TextInput::make('discount_amount_cents')
                            ->label('Fixed Discount (cents)')
                            ->numeric()
                            ->minValue(0)
                            ->helperText('Amount in cents. Use this OR percentage, not both.'),
                    ])->columns(2),
                Forms\Components\Section::make('Settings')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options(collect(AdStatus::cases())
                                ->mapWithKeys(fn (AdStatus $s): array => [$s->value => $s->label()]))
                            ->required()
                            ->default(AdStatus::Draft->value)
                            ->native(false),
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
                Infolists\Components\Section::make('Promotion')
                    ->schema([
                        Infolists\Components\TextEntry::make('name')
                            ->weight('bold')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large),
                        Infolists\Components\TextEntry::make('type')
                            ->badge()
                            ->formatStateUsing(fn ($state): string => $state instanceof PromotionType ? $state->label() : ucfirst((string) $state))
                            ->color('info'),
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
                        Infolists\Components\TextEntry::make('discount_percentage')
                            ->label('Discount %')
                            ->suffix('%')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('discount_amount_cents')
                            ->label('Fixed Amount')
                            ->formatStateUsing(fn (?int $state): string => $state ? '₱'.number_format($state / 100, 2) : '—'),
                        Infolists\Components\TextEntry::make('campaign.name')
                            ->label('Campaign')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('author.name')
                            ->label('Created By')
                            ->default('System'),
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
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->limit(40),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof PromotionType ? $state->label() : ucfirst((string) $state))
                    ->color('info')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof AdStatus ? $state->label() : ucfirst((string) $state))
                    ->color(fn ($state): string => $state instanceof AdStatus ? $state->color() : 'gray')
                    ->sortable(),
                Tables\Columns\TextColumn::make('discount_percentage')
                    ->label('Discount')
                    ->suffix('%')
                    ->placeholder('—')
                    ->sortable(),
                Tables\Columns\TextColumn::make('discount_amount_cents')
                    ->label('Fixed')
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
                Tables\Filters\SelectFilter::make('type')
                    ->options(collect(PromotionType::cases())
                        ->mapWithKeys(fn (PromotionType $t): array => [$t->value => $t->label()])),
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
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPromotions::route('/'),
            'create' => Pages\CreatePromotion::route('/create'),
            'view' => Pages\ViewPromotion::route('/{record}'),
            'edit' => Pages\EditPromotion::route('/{record}/edit'),
        ];
    }
}
