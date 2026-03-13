<?php

namespace App\Filament\Realty\Resources;

use App\Filament\Realty\Resources\DevelopmentResource\Pages;
use App\Filament\Realty\Resources\DevelopmentResource\RelationManagers;
use App\Models\Development;
use App\SectorTemplate;
use Filament\Forms;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DevelopmentResource extends Resource
{
    /**
     * Rental stores do not manage developments.
     */
    public static function canAccess(): bool
    {
        return auth()->user()?->getStoreForPanel()?->template() !== SectorTemplate::Rental;
    }

    protected static ?string $model = Development::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = 'Projects';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $modelLabel = 'Development';

    protected static ?string $pluralModelLabel = 'Developments';

    /**
     * Scope all queries to the current user's store.
     */
    public static function getEloquentQuery(): Builder
    {
        $store = auth()->user()?->getStoreForPanel();

        return parent::getEloquentQuery()
            ->where('store_id', $store?->id)
            ->withCount('properties');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Development')
                    ->tabs([
                        // ── Details Tab ────────────────────────────────
                        Forms\Components\Tabs\Tab::make('Details')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Forms\Set $set, ?string $state) => $set('slug', Str::slug($state).'-'.Str::random(6))),

                                Forms\Components\TextInput::make('slug')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->helperText('Auto-generated from name.'),

                                Forms\Components\TextInput::make('developer_name')
                                    ->label('Developer / Builder')
                                    ->maxLength(255)
                                    ->placeholder('e.g. Ayala Land, Megaworld'),

                                Forms\Components\Select::make('development_type')
                                    ->options([
                                        'condominium' => 'Condominium',
                                        'subdivision' => 'Subdivision',
                                        'township' => 'Township',
                                        'mixed_use' => 'Mixed Use',
                                        'commercial_complex' => 'Commercial Complex',
                                    ])
                                    ->default('condominium')
                                    ->required()
                                    ->native(false),

                                Forms\Components\Select::make('status')
                                    ->options([
                                        'draft' => 'Draft',
                                        'active' => 'Active',
                                        'sold_out' => 'Sold Out',
                                        'archived' => 'Archived',
                                    ])
                                    ->default('active')
                                    ->required()
                                    ->native(false),

                                Forms\Components\RichEditor::make('description')
                                    ->columnSpanFull()
                                    ->maxLength(5000),
                            ])->columns(2),

                        // ── Specifications Tab ─────────────────────────
                        Forms\Components\Tabs\Tab::make('Specifications')
                            ->icon('heroicon-o-wrench-screwdriver')
                            ->schema([
                                Forms\Components\TextInput::make('total_units')
                                    ->numeric()
                                    ->minValue(1),

                                Forms\Components\TextInput::make('available_units')
                                    ->numeric()
                                    ->minValue(0)
                                    ->helperText('Auto-synced from listings if left blank.'),

                                Forms\Components\TextInput::make('floors')
                                    ->label('Number of Floors')
                                    ->numeric()
                                    ->minValue(1),

                                Forms\Components\TextInput::make('year_built')
                                    ->numeric()
                                    ->minValue(1900)
                                    ->maxValue(date('Y') + 10),

                                Forms\Components\TextInput::make('price_range_min')
                                    ->label('Minimum Price')
                                    ->numeric()
                                    ->prefix('₱')
                                    ->minValue(0),

                                Forms\Components\TextInput::make('price_range_max')
                                    ->label('Maximum Price')
                                    ->numeric()
                                    ->prefix('₱')
                                    ->minValue(0),
                            ])->columns(3),

                        // ── Location Tab ───────────────────────────────
                        Forms\Components\Tabs\Tab::make('Location')
                            ->icon('heroicon-o-map-pin')
                            ->schema([
                                Forms\Components\TextInput::make('address_line')
                                    ->label('Street Address')
                                    ->maxLength(255)
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('barangay')
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('city')
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('province')
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('zip_code')
                                    ->maxLength(10),

                                Forms\Components\TextInput::make('latitude')
                                    ->numeric()
                                    ->step(0.000001),

                                Forms\Components\TextInput::make('longitude')
                                    ->numeric()
                                    ->step(0.000001),
                            ])->columns(2),

                        // ── Amenities Tab ──────────────────────────────
                        Forms\Components\Tabs\Tab::make('Amenities')
                            ->icon('heroicon-o-sparkles')
                            ->schema([
                                Forms\Components\TagsInput::make('amenities')
                                    ->placeholder('Add amenity...')
                                    ->suggestions([
                                        'Swimming Pool',
                                        'Gym / Fitness Center',
                                        'Clubhouse',
                                        'Function Room',
                                        'Playground',
                                        'Jogging Path',
                                        'Basketball Court',
                                        'Landscaped Garden',
                                        'Sky Lounge',
                                        'Co-Working Space',
                                        'Concierge',
                                        'Retail Area',
                                        'Covered Parking',
                                        'CCTV Surveillance',
                                        'Fire Alarm System',
                                        'Elevator',
                                        'Back-Up Power',
                                        'Water Treatment',
                                    ])
                                    ->columnSpanFull(),
                            ]),

                        // ── Media Tab ──────────────────────────────────
                        Forms\Components\Tabs\Tab::make('Media')
                            ->icon('heroicon-o-photo')
                            ->schema([
                                SpatieMediaLibraryFileUpload::make('logo')
                                    ->label('Logo')
                                    ->collection('logo')
                                    ->image()
                                    ->imagePreviewHeight('120')
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'])
                                    ->maxSize(2048),

                                Forms\Components\TextInput::make('website_url')
                                    ->label('Website')
                                    ->url()
                                    ->maxLength(500),

                                Forms\Components\TextInput::make('video_url')
                                    ->label('Video URL')
                                    ->url()
                                    ->maxLength(500),

                                SpatieMediaLibraryFileUpload::make('images')
                                    ->label('Images')
                                    ->collection('images')
                                    ->multiple()
                                    ->reorderable()
                                    ->image()
                                    ->imagePreviewHeight('120')
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->maxSize(5120)
                                    ->panelLayout('grid')
                                    ->columnSpanFull(),
                            ])->columns(2),

                        // ── Publishing Tab ─────────────────────────────
                        Forms\Components\Tabs\Tab::make('Publishing')
                            ->icon('heroicon-o-globe-alt')
                            ->schema([
                                Forms\Components\Toggle::make('is_featured')
                                    ->label('Featured Development')
                                    ->helperText('Featured developments appear prominently on the browse page.'),

                                Forms\Components\DateTimePicker::make('published_at')
                                    ->label('Publish Date'),
                            ]),
                    ])
                    ->persistTabInQueryString()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->tooltip(fn (Model $record): string => $record->name),

                Tables\Columns\TextColumn::make('developer_name')
                    ->label('Developer')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('development_type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => str_replace('_', ' ', ucfirst($state)))
                    ->color('primary')
                    ->sortable(),

                Tables\Columns\TextColumn::make('city')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => str_replace('_', ' ', ucfirst($state)))
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'draft' => 'gray',
                        'sold_out' => 'danger',
                        'archived' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('properties_count')
                    ->label('Units Listed')
                    ->sortable()
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('total_units')
                    ->label('Total Units')
                    ->sortable()
                    ->alignEnd()
                    ->placeholder('—'),

                Tables\Columns\IconColumn::make('is_featured')
                    ->boolean()
                    ->label('Featured')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('development_type')
                    ->options([
                        'condominium' => 'Condominium',
                        'subdivision' => 'Subdivision',
                        'township' => 'Township',
                        'mixed_use' => 'Mixed Use',
                        'commercial_complex' => 'Commercial Complex',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'active' => 'Active',
                        'sold_out' => 'Sold Out',
                        'archived' => 'Archived',
                    ]),

                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('Featured'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            RelationManagers\PropertiesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDevelopments::route('/'),
            'create' => Pages\CreateDevelopment::route('/create'),
            'edit' => Pages\EditDevelopment::route('/{record}/edit'),
        ];
    }
}
