<?php

namespace App\Filament\LipatBahay\Resources;

use App\Filament\LipatBahay\Resources\MovingBookingResource\Pages;
use App\Models\MovingBooking;
use App\MovingBookingStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MovingBookingResource extends Resource
{
    protected static ?string $model = MovingBooking::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationGroup = 'Bookings';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Moving Booking';

    protected static ?string $pluralModelLabel = 'Moving Bookings';

    public static function getEloquentQuery(): Builder
    {
        $store = auth()->user()?->getStoreForPanel();

        return parent::getEloquentQuery()
            ->where('store_id', $store?->id)
            ->with(['customer', 'addOns', 'rentalAgreement']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Customer & Contact')
                    ->schema([
                        Forms\Components\TextInput::make('contact_name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('contact_phone')
                            ->tel()
                            ->required()
                            ->maxLength(30),

                        Forms\Components\TextInput::make('notes')
                            ->maxLength(1000)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Move Details')
                    ->schema([
                        Forms\Components\TextInput::make('pickup_address')
                            ->required()
                            ->maxLength(500),

                        Forms\Components\TextInput::make('delivery_address')
                            ->required()
                            ->maxLength(500),

                        Forms\Components\TextInput::make('pickup_city')
                            ->required()
                            ->maxLength(100),

                        Forms\Components\TextInput::make('delivery_city')
                            ->required()
                            ->maxLength(100),

                        Forms\Components\DateTimePicker::make('scheduled_at')
                            ->required()
                            ->native(false),
                    ])->columns(2),

                Forms\Components\Section::make('Status & Pricing')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options(collect(MovingBookingStatus::cases())
                                ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
                                ->toArray())
                            ->required(),

                        Forms\Components\TextInput::make('base_price')
                            ->label('Base Price (₱)')
                            ->numeric()
                            ->minValue(0)
                            ->helperText('In pesos. Stored internally as centavos.'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('contact_name')
                    ->label('Customer')
                    ->searchable(),

                Tables\Columns\TextColumn::make('pickup_city')
                    ->label('From')
                    ->searchable(),

                Tables\Columns\TextColumn::make('delivery_city')
                    ->label('To')
                    ->searchable(),

                Tables\Columns\TextColumn::make('scheduled_at')
                    ->dateTime('M j, Y g:i A')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (MovingBookingStatus $state) => $state->color())
                    ->formatStateUsing(fn (MovingBookingStatus $state) => $state->label()),

                Tables\Columns\TextColumn::make('total_price')
                    ->label('Total')
                    ->formatStateUsing(fn (int $state) => '₱'.number_format($state / 100, 2))
                    ->sortable(),
            ])
            ->defaultSort('scheduled_at', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(MovingBookingStatus::cases())
                        ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
                        ->toArray()),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMovingBookings::route('/'),
            'view' => Pages\ViewMovingBooking::route('/{record}'),
            'edit' => Pages\EditMovingBooking::route('/{record}/edit'),
        ];
    }
}
