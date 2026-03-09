<?php

namespace App\Filament\Admin\Resources;

use App\AdStatus;
use App\CouponScope;
use App\CouponType;
use App\Filament\Admin\Resources\CouponResource\Pages;
use App\IndustrySector;
use App\Models\Coupon;
use App\Models\Store;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationGroup = 'Marketing';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Coupon Details')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->required()
                            ->maxLength(50)
                            ->unique(ignoreRecord: true)
                            ->helperText('Unique coupon code customers will enter at checkout.'),
                        Forms\Components\Select::make('type')
                            ->options(collect(CouponType::cases())
                                ->mapWithKeys(fn (CouponType $t): array => [$t->value => $t->label()]))
                            ->required()
                            ->native(false)
                            ->live(),
                        Forms\Components\TextInput::make('description')
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ])->columns(2),
                Forms\Components\Section::make('Value')
                    ->schema([
                        Forms\Components\TextInput::make('value')
                            ->label(fn (Forms\Get $get): string => match ($get('type')) {
                                CouponType::Percentage->value => 'Percentage (%)',
                                CouponType::FixedAmount->value => 'Amount (cents)',
                                default => 'Value',
                            })
                            ->numeric()
                            ->required()
                            ->minValue(0),
                        Forms\Components\TextInput::make('min_order_cents')
                            ->label('Minimum Order (cents)')
                            ->numeric()
                            ->minValue(0)
                            ->helperText('Minimum order amount required to use this coupon.'),
                        Forms\Components\TextInput::make('max_discount_cents')
                            ->label('Max Discount (cents)')
                            ->numeric()
                            ->minValue(0)
                            ->helperText('Cap the maximum discount. Leave empty for no cap.'),
                    ])->columns(3),
                Forms\Components\Section::make('Scope')
                    ->schema([
                        Forms\Components\Select::make('scope')
                            ->options(collect(CouponScope::cases())
                                ->mapWithKeys(fn (CouponScope $s): array => [$s->value => $s->label()]))
                            ->required()
                            ->native(false)
                            ->live(),
                        Forms\Components\Select::make('sector')
                            ->options(collect(IndustrySector::cases())
                                ->mapWithKeys(fn (IndustrySector $s): array => [$s->value => $s->label()]))
                            ->visible(fn (Forms\Get $get): bool => $get('scope') === CouponScope::Sector->value)
                            ->required(fn (Forms\Get $get): bool => $get('scope') === CouponScope::Sector->value)
                            ->native(false),
                        Forms\Components\Select::make('store_id')
                            ->label('Store')
                            ->options(Store::query()->approved()->pluck('name', 'id'))
                            ->visible(fn (Forms\Get $get): bool => $get('scope') === CouponScope::Store->value)
                            ->required(fn (Forms\Get $get): bool => $get('scope') === CouponScope::Store->value)
                            ->searchable(),
                    ])->columns(3),
                Forms\Components\Section::make('Usage & Status')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options(collect(AdStatus::cases())
                                ->mapWithKeys(fn (AdStatus $s): array => [$s->value => $s->label()]))
                            ->required()
                            ->default(AdStatus::Draft->value)
                            ->native(false),
                        Forms\Components\TextInput::make('max_uses')
                            ->label('Max Uses')
                            ->numeric()
                            ->minValue(1)
                            ->helperText('Leave empty for unlimited.'),
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
                Infolists\Components\Section::make('Coupon')
                    ->schema([
                        Infolists\Components\TextEntry::make('code')
                            ->weight('bold')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                            ->copyable(),
                        Infolists\Components\TextEntry::make('type')
                            ->badge()
                            ->formatStateUsing(fn ($state): string => $state instanceof CouponType ? $state->label() : ucfirst((string) $state))
                            ->color('info'),
                        Infolists\Components\TextEntry::make('scope')
                            ->badge()
                            ->formatStateUsing(fn ($state): string => $state instanceof CouponScope ? $state->label() : ucfirst((string) $state))
                            ->color('gray'),
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->formatStateUsing(fn ($state): string => $state instanceof AdStatus ? $state->label() : ucfirst((string) $state))
                            ->color(fn ($state): string => $state instanceof AdStatus ? $state->color() : 'gray'),
                        Infolists\Components\TextEntry::make('description')
                            ->columnSpanFull()
                            ->placeholder('—'),
                    ])->columns(4),
                Infolists\Components\Section::make('Value & Restrictions')
                    ->schema([
                        Infolists\Components\TextEntry::make('value')
                            ->label('Discount Value'),
                        Infolists\Components\TextEntry::make('min_order_cents')
                            ->label('Min Order')
                            ->formatStateUsing(fn (?int $state): string => $state ? '₱'.number_format($state / 100, 2) : '—'),
                        Infolists\Components\TextEntry::make('max_discount_cents')
                            ->label('Max Discount')
                            ->formatStateUsing(fn (?int $state): string => $state ? '₱'.number_format($state / 100, 2) : 'No cap'),
                    ])->columns(3),
                Infolists\Components\Section::make('Usage')
                    ->schema([
                        Infolists\Components\TextEntry::make('times_used')
                            ->label('Times Used'),
                        Infolists\Components\TextEntry::make('max_uses')
                            ->label('Max Uses')
                            ->placeholder('Unlimited'),
                        Infolists\Components\TextEntry::make('store.name')
                            ->label('Store')
                            ->placeholder('—'),
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
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof CouponType ? $state->label() : ucfirst((string) $state))
                    ->color('info')
                    ->sortable(),
                Tables\Columns\TextColumn::make('scope')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof CouponScope ? $state->label() : ucfirst((string) $state))
                    ->color('gray')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof AdStatus ? $state->label() : ucfirst((string) $state))
                    ->color(fn ($state): string => $state instanceof AdStatus ? $state->color() : 'gray')
                    ->sortable(),
                Tables\Columns\TextColumn::make('value')
                    ->sortable(),
                Tables\Columns\TextColumn::make('times_used')
                    ->label('Used')
                    ->getStateUsing(fn (Coupon $record): string => $record->max_uses
                        ? "{$record->times_used}/{$record->max_uses}"
                        : (string) $record->times_used)
                    ->sortable(),
                Tables\Columns\TextColumn::make('store.name')
                    ->label('Store')
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('ends_at')
                    ->label('Expires')
                    ->dateTime('M d, Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options(collect(CouponType::cases())
                        ->mapWithKeys(fn (CouponType $t): array => [$t->value => $t->label()])),
                Tables\Filters\SelectFilter::make('scope')
                    ->options(collect(CouponScope::cases())
                        ->mapWithKeys(fn (CouponScope $s): array => [$s->value => $s->label()])),
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
            'index' => Pages\ListCoupons::route('/'),
            'create' => Pages\CreateCoupon::route('/create'),
            'view' => Pages\ViewCoupon::route('/{record}'),
            'edit' => Pages\EditCoupon::route('/{record}/edit'),
        ];
    }
}
