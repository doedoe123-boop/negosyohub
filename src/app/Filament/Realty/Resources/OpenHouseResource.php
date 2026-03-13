<?php

namespace App\Filament\Realty\Resources;

use App\Filament\Realty\Resources\OpenHouseResource\Pages;
use App\Filament\Realty\Resources\OpenHouseResource\RelationManagers;
use App\Models\OpenHouse;
use App\Models\Property;
use App\SectorTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class OpenHouseResource extends Resource
{
    /**
     * Rental stores do not manage open houses.
     */
    public static function canAccess(): bool
    {
        return auth()->user()?->getStoreForPanel()?->template() !== SectorTemplate::Rental;
    }

    protected static ?string $model = OpenHouse::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Listings';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'title';

    /**
     * Show badge with upcoming event count.
     */
    public static function getNavigationBadge(): ?string
    {
        $store = auth()->user()?->getStoreForPanel();
        if (! $store) {
            return null;
        }

        $count = OpenHouse::forStore($store->id)->upcoming()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }

    public static function getEloquentQuery(): Builder
    {
        $store = auth()->user()?->getStoreForPanel();

        return parent::getEloquentQuery()
            ->where('store_id', $store?->id)
            ->with('property')
            ->withCount('rsvps');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Event Details')
                    ->schema([
                        Forms\Components\Select::make('property_id')
                            ->label('Property')
                            ->options(function (): array {
                                $store = auth()->user()?->getStoreForPanel();

                                return Property::query()
                                    ->where('store_id', $store?->id)
                                    ->orderBy('title')
                                    ->pluck('title', 'id')
                                    ->toArray();
                            })
                            ->required()
                            ->searchable()
                            ->preload()
                            ->native(false),

                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->default('Open House Weekend'),

                        Forms\Components\RichEditor::make('description')
                            ->columnSpanFull()
                            ->maxLength(2000),
                    ])->columns(2),

                Forms\Components\Section::make('Schedule')
                    ->schema([
                        Forms\Components\DatePicker::make('event_date')
                            ->required()
                            ->minDate(now()),

                        Forms\Components\TimePicker::make('start_time')
                            ->required()
                            ->seconds(false),

                        Forms\Components\TimePicker::make('end_time')
                            ->required()
                            ->seconds(false)
                            ->after('start_time'),

                        Forms\Components\Select::make('status')
                            ->options([
                                'scheduled' => 'Scheduled',
                                'ongoing' => 'Ongoing',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('scheduled')
                            ->required()
                            ->native(false),
                    ])->columns(4),

                Forms\Components\Section::make('Attendance & Virtual')
                    ->schema([
                        Forms\Components\TextInput::make('max_attendees')
                            ->numeric()
                            ->minValue(1)
                            ->placeholder('Unlimited'),

                        Forms\Components\Toggle::make('is_virtual')
                            ->label('Virtual Event')
                            ->live(),

                        Forms\Components\TextInput::make('virtual_link')
                            ->url()
                            ->visible(fn (Forms\Get $get): bool => (bool) $get('is_virtual'))
                            ->required(fn (Forms\Get $get): bool => (bool) $get('is_virtual'))
                            ->placeholder('https://zoom.us/j/...'),
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
                    ->limit(35),

                Tables\Columns\TextColumn::make('property.title')
                    ->label('Property')
                    ->limit(30)
                    ->sortable(),

                Tables\Columns\TextColumn::make('event_date')
                    ->date('M d, Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('start_time')
                    ->label('Time')
                    ->formatStateUsing(fn (Model $record): string => $record->timeRange()),

                Tables\Columns\TextColumn::make('rsvps_count')
                    ->label('RSVPs')
                    ->sortable()
                    ->alignEnd()
                    ->suffix(fn (Model $record): string => $record->max_attendees ? " / {$record->max_attendees}" : ''),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'scheduled' => 'info',
                        'ongoing' => 'success',
                        'completed' => 'gray',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_virtual')
                    ->boolean()
                    ->label('Virtual')
                    ->sortable(),
            ])
            ->defaultSort('event_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'scheduled' => 'Scheduled',
                        'ongoing' => 'Ongoing',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),

                Tables\Filters\TernaryFilter::make('is_virtual')
                    ->label('Virtual'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Model $record): bool => $record->status === 'scheduled')
                    ->action(fn (Model $record) => $record->cancel()),
                Tables\Actions\Action::make('complete')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Model $record): bool => in_array($record->status, ['scheduled', 'ongoing']))
                    ->action(fn (Model $record) => $record->complete()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\RsvpsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOpenHouses::route('/'),
            'create' => Pages\CreateOpenHouse::route('/create'),
            'edit' => Pages\EditOpenHouse::route('/{record}/edit'),
        ];
    }
}
