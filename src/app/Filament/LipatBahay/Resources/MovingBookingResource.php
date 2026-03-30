<?php

namespace App\Filament\LipatBahay\Resources;

use App\Filament\LipatBahay\Resources\MovingBookingResource\Pages;
use App\Models\MovingBooking;
use App\MovingBookingStatus;
use App\Services\MovingBookingService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
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
                        Forms\Components\TextInput::make('base_price')
                            ->label('Base Price (₱)')
                            ->numeric()
                            ->minValue(0)
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Derived from the provider profile and stored internally as centavos.'),
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
                static::transitionTableAction(
                    'confirm',
                    'Confirm',
                    'heroicon-o-check-circle',
                    'info',
                    MovingBookingStatus::Confirmed,
                ),
                static::transitionTableAction(
                    'start_move',
                    'Start Move',
                    'heroicon-o-arrow-path',
                    'primary',
                    MovingBookingStatus::InProgress,
                ),
                static::transitionTableAction(
                    'complete',
                    'Complete',
                    'heroicon-o-check-badge',
                    'success',
                    MovingBookingStatus::Completed,
                ),
                static::transitionTableAction(
                    'cancel',
                    'Cancel',
                    'heroicon-o-x-circle',
                    'danger',
                    MovingBookingStatus::Cancelled,
                ),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMovingBookings::route('/'),
            'view' => Pages\ViewMovingBooking::route('/{record}'),
        ];
    }

    public static function transitionAction(
        string $name,
        string $label,
        string $icon,
        string $color,
        MovingBookingStatus $target
    ): Action {
        return Action::make($name)
            ->label($label)
            ->icon($icon)
            ->color($color)
            ->requiresConfirmation()
            ->visible(fn (MovingBooking $record): bool => $record->status->canTransitionTo($target))
            ->action(function (MovingBooking $record) use ($target, $label): void {
                app(MovingBookingService::class)->updateStatus($record, $target);

                Notification::make()
                    ->title("Booking {$label}")
                    ->success()
                    ->send();
            });
    }

    private static function transitionTableAction(
        string $name,
        string $label,
        string $icon,
        string $color,
        MovingBookingStatus $target
    ): Action {
        return static::transitionAction($name, $label, $icon, $color, $target)
            ->hiddenLabel();
    }
}
