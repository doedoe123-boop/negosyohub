<?php

namespace App\Filament\Realty\Resources;

use App\Filament\Realty\Resources\PropertyResource\Pages;
use App\Filament\Realty\Resources\PropertyResource\RelationManagers;
use App\ListingType;
use App\Models\Development;
use App\Models\RentalAgreement;
use App\PropertyStatus;
use App\PropertyType;
use App\SectorTemplate;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PropertyResource extends Resource
{
    protected static ?string $model = \App\Models\Property::class;

    protected static ?string $navigationIcon = 'heroicon-o-home-modern';

    protected static ?string $navigationGroup = 'Listings';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'title';

    /**
     * Scope all queries to the current user's store.
     */
    public static function getEloquentQuery(): Builder
    {
        $store = auth()->user()?->getStoreForPanel();

        return parent::getEloquentQuery()
            ->where('store_id', $store?->id)
            ->withCount('testimonials')
            ->withAvg('testimonials as avg_rating', 'rating');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Property')
                    ->tabs([
                        // ── Details Tab ────────────────────────────────
                        Forms\Components\Tabs\Tab::make('Details')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Forms\Components\TextInput::make('title')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Forms\Set $set, ?string $state) => $set('slug', Str::slug($state).'-'.Str::random(6))),

                                Forms\Components\TextInput::make('slug')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->helperText('Auto-generated from title. You can customise it.'),

                                Forms\Components\RichEditor::make('description')
                                    ->columnSpanFull()
                                    ->maxLength(5000),

                                Forms\Components\Select::make('property_type')
                                    ->options(collect(PropertyType::cases())->mapWithKeys(
                                        fn (PropertyType $type) => [$type->value => $type->label()]
                                    ))
                                    ->required()
                                    ->native(false)
                                    ->live(),

                                Forms\Components\Select::make('listing_type')
                                    ->options(function (): array {
                                        $store = auth()->user()?->getStoreForPanel();
                                        $allowed = $store?->template() === SectorTemplate::Rental
                                            ? [ListingType::ForRent, ListingType::ForLease]
                                            : ListingType::cases();

                                        return collect($allowed)->mapWithKeys(
                                            fn (ListingType $type) => [$type->value => $type->label()]
                                        )->toArray();
                                    })
                                    ->required()
                                    ->native(false)
                                    ->live(),

                                Forms\Components\Select::make('status')
                                    ->options(collect(PropertyStatus::cases())->mapWithKeys(
                                        fn (PropertyStatus $s) => [$s->value => $s->label()]
                                    ))
                                    ->default(PropertyStatus::Draft->value)
                                    ->required()
                                    ->native(false),

                                Forms\Components\Section::make('Development / Project')
                                    ->description('Link this property to a development project (optional).')
                                    ->collapsible()
                                    ->collapsed()
                                    ->visible(fn (): bool => auth()->user()?->getStoreForPanel()?->template() !== SectorTemplate::Rental)
                                    ->schema([
                                        Forms\Components\Select::make('development_id')
                                            ->label('Development')
                                            ->options(function (): array {
                                                $store = auth()->user()?->getStoreForPanel();

                                                return Development::query()
                                                    ->where('store_id', $store?->id)
                                                    ->orderBy('name')
                                                    ->pluck('name', 'id')
                                                    ->toArray();
                                            })
                                            ->searchable()
                                            ->preload()
                                            ->native(false)
                                            ->live()
                                            ->placeholder('None — standalone listing'),

                                        Forms\Components\TextInput::make('unit_number')
                                            ->label('Unit Number')
                                            ->maxLength(50)
                                            ->placeholder('e.g. Unit 12B')
                                            ->visible(fn (Get $get): bool => filled($get('development_id'))),

                                        Forms\Components\TextInput::make('unit_floor')
                                            ->label('Floor Level')
                                            ->maxLength(50)
                                            ->placeholder('e.g. 12th Floor')
                                            ->visible(fn (Get $get): bool => filled($get('development_id'))),
                                    ])
                                    ->columns(3)
                                    ->columnSpanFull(),
                            ])->columns(2),

                        // ── Pricing Tab ────────────────────────────────
                        Forms\Components\Tabs\Tab::make('Pricing')
                            ->icon('heroicon-o-currency-dollar')
                            ->schema([
                                Forms\Components\TextInput::make('price')
                                    ->numeric()
                                    ->required()
                                    ->prefix('₱')
                                    ->minValue(0)
                                    ->step(0.01),

                                Forms\Components\Select::make('price_currency')
                                    ->options([
                                        'PHP' => 'PHP (₱)',
                                        'USD' => 'USD ($)',
                                    ])
                                    ->default('PHP')
                                    ->native(false),

                                Forms\Components\Select::make('price_period')
                                    ->options([
                                        'month' => 'Per Month',
                                        'year' => 'Per Year',
                                        'sqm' => 'Per SQM',
                                    ])
                                    ->visible(fn (Get $get): bool => in_array($get('listing_type'), [
                                        ListingType::ForRent->value,
                                        ListingType::ForLease->value,
                                    ]))
                                    ->native(false),
                            ])->columns(3),

                        // ── Specifications Tab ─────────────────────────
                        Forms\Components\Tabs\Tab::make('Specifications')
                            ->icon('heroicon-o-wrench-screwdriver')
                            ->schema([
                                Forms\Components\TextInput::make('bedrooms')
                                    ->numeric()
                                    ->minValue(0)
                                    ->visible(fn (Get $get): bool => ! in_array($get('property_type'), [
                                        PropertyType::Lot->value,
                                        PropertyType::Warehouse->value,
                                        PropertyType::Farm->value,
                                    ])),

                                Forms\Components\TextInput::make('bathrooms')
                                    ->numeric()
                                    ->minValue(0)
                                    ->visible(fn (Get $get): bool => ! in_array($get('property_type'), [
                                        PropertyType::Lot->value,
                                        PropertyType::Farm->value,
                                    ])),

                                Forms\Components\TextInput::make('garage_spaces')
                                    ->label('Garage / Parking Spaces')
                                    ->numeric()
                                    ->minValue(0),

                                Forms\Components\TextInput::make('floor_area')
                                    ->label('Floor Area (sqm)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->visible(fn (Get $get): bool => $get('property_type') !== PropertyType::Lot->value),

                                Forms\Components\TextInput::make('lot_area')
                                    ->label('Lot Area (sqm)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(0.01),

                                Forms\Components\TextInput::make('year_built')
                                    ->numeric()
                                    ->minValue(1900)
                                    ->maxValue(date('Y') + 5)
                                    ->visible(fn (Get $get): bool => ! in_array($get('property_type'), [
                                        PropertyType::Lot->value,
                                        PropertyType::Farm->value,
                                    ])),

                                Forms\Components\TextInput::make('floors')
                                    ->label('Number of Floors')
                                    ->numeric()
                                    ->minValue(1)
                                    ->visible(fn (Get $get): bool => in_array($get('property_type'), [
                                        PropertyType::House->value,
                                        PropertyType::Townhouse->value,
                                        PropertyType::Commercial->value,
                                        PropertyType::Warehouse->value,
                                    ])),
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

                        // ── Features Tab ───────────────────────────────
                        Forms\Components\Tabs\Tab::make('Features')
                            ->icon('heroicon-o-sparkles')
                            ->visible(fn (): bool => auth()->user()?->getStoreForPanel()?->template() !== SectorTemplate::Rental)
                            ->schema([
                                Forms\Components\TagsInput::make('features')
                                    ->placeholder('Add feature...')
                                    ->suggestions([
                                        'Swimming Pool',
                                        'Garden',
                                        'Parking',
                                        'Security',
                                        'Gym',
                                        'Balcony',
                                        'Elevator',
                                        'CCTV',
                                        'Air Conditioning',
                                        'Furnished',
                                        'Pet Friendly',
                                        'Rooftop',
                                        'Storage Room',
                                        'Laundry Area',
                                        'Playground',
                                    ])
                                    ->columnSpanFull(),
                            ]),

                        // ── Media Tab ──────────────────────────────────
                        Forms\Components\Tabs\Tab::make('Media')
                            ->icon('heroicon-o-photo')
                            ->schema([
                                SpatieMediaLibraryFileUpload::make('images')
                                    ->label('Property Images')
                                    ->collection('images')
                                    ->multiple()
                                    ->reorderable()
                                    ->image()
                                    ->imagePreviewHeight('120')
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif'])
                                    ->maxSize(5120)
                                    ->panelLayout('grid')
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('video_url')
                                    ->label('Video URL')
                                    ->url()
                                    ->maxLength(500),

                                Forms\Components\TextInput::make('virtual_tour_url')
                                    ->label('Virtual Tour URL')
                                    ->url()
                                    ->maxLength(500),
                            ])->columns(2),

                        // ── Floor Plans Tab ────────────────────────────
                        Forms\Components\Tabs\Tab::make('Floor Plans')
                            ->icon('heroicon-o-map')
                            ->schema([
                                Forms\Components\Repeater::make('floor_plans')
                                    ->schema([
                                        FileUpload::make('url')
                                            ->label('Floor Plan Image / PDF')
                                            ->disk('public')
                                            ->directory('properties/floor-plans')
                                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'application/pdf'])
                                            ->maxSize(10240)
                                            ->image()
                                            ->imagePreviewHeight('80')
                                            ->columnSpan(3),
                                        Forms\Components\TextInput::make('label')
                                            ->label('Label')
                                            ->maxLength(100)
                                            ->placeholder('e.g. Ground Floor, 2BR Unit Layout'),
                                        Forms\Components\TextInput::make('floor_number')
                                            ->label('Floor #')
                                            ->numeric()
                                            ->minValue(0),
                                    ])
                                    ->columns(3)
                                    ->collapsible()
                                    ->defaultItems(0)
                                    ->addActionLabel('Add Floor Plan')
                                    ->columnSpanFull(),
                            ]),

                        // ── Documents Tab ──────────────────────────────
                        Forms\Components\Tabs\Tab::make('Documents')
                            ->icon('heroicon-o-document-arrow-down')
                            ->schema([
                                Forms\Components\Repeater::make('documents')
                                    ->schema([
                                        FileUpload::make('url')
                                            ->label('File')
                                            ->disk('public')
                                            ->directory('properties/documents')
                                            ->acceptedFileTypes([
                                                'application/pdf',
                                                'application/msword',
                                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                                'image/jpeg',
                                                'image/png',
                                            ])
                                            ->maxSize(20480)
                                            ->columnSpan(3),
                                        Forms\Components\TextInput::make('name')
                                            ->label('Document Name')
                                            ->required()
                                            ->maxLength(255)
                                            ->placeholder('e.g. Price List Q1 2026'),
                                        Forms\Components\Select::make('type')
                                            ->options([
                                                'brochure' => 'Brochure',
                                                'price_list' => 'Price List',
                                                'floor_plan' => 'Floor Plan PDF',
                                                'contract' => 'Sample Contract',
                                                'terms' => 'Terms & Conditions',
                                                'other' => 'Other',
                                            ])
                                            ->default('brochure')
                                            ->native(false),
                                    ])
                                    ->columns(3)
                                    ->collapsible()
                                    ->defaultItems(0)
                                    ->addActionLabel('Add Document')
                                    ->columnSpanFull(),
                            ]),

                        // ── Neighborhood Tab ───────────────────────────
                        Forms\Components\Tabs\Tab::make('Neighborhood')
                            ->icon('heroicon-o-building-storefront')
                            ->schema([
                                Forms\Components\Repeater::make('nearby_places')
                                    ->label('Nearby Places')
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Place Name')
                                            ->required()
                                            ->maxLength(255)
                                            ->placeholder('e.g. SM Makati'),
                                        Forms\Components\Select::make('type')
                                            ->options([
                                                'school' => 'School',
                                                'hospital' => 'Hospital',
                                                'mall' => 'Mall / Shopping',
                                                'transport' => 'Transport Hub',
                                                'restaurant' => 'Restaurant',
                                                'park' => 'Park / Recreation',
                                                'church' => 'Church / Worship',
                                                'bank' => 'Bank',
                                                'government' => 'Government Office',
                                                'other' => 'Other',
                                            ])
                                            ->required()
                                            ->native(false),
                                        Forms\Components\TextInput::make('distance')
                                            ->numeric()
                                            ->minValue(0)
                                            ->step(0.1)
                                            ->placeholder('e.g. 1.5'),
                                        Forms\Components\Select::make('distance_unit')
                                            ->options([
                                                'km' => 'km',
                                                'm' => 'meters',
                                                'min_walk' => 'min walk',
                                                'min_drive' => 'min drive',
                                            ])
                                            ->default('km')
                                            ->native(false),
                                    ])
                                    ->columns(4)
                                    ->collapsible()
                                    ->defaultItems(0)
                                    ->addActionLabel('Add Nearby Place')
                                    ->columnSpanFull(),
                            ]),

                        // ── Paano Pumunta (Directions) Tab ─────────────
                        Forms\Components\Tabs\Tab::make('Paano Pumunta')
                            ->icon('heroicon-o-map')
                            ->visible(fn (): bool => auth()->user()?->getStoreForPanel()?->template() === SectorTemplate::Rental)
                            ->schema([
                                Forms\Components\Placeholder::make('directions_help')
                                    ->label('')
                                    ->content('Add step-by-step directions to help tenants find your property. Include landmarks, turns, and photos so they don\'t need to rely on GPS alone.')
                                    ->columnSpanFull(),

                                Forms\Components\Repeater::make('direction_steps')
                                    ->label('Direction Steps')
                                    ->schema([
                                        Forms\Components\TextInput::make('instruction')
                                            ->label('Instruction')
                                            ->required()
                                            ->maxLength(500)
                                            ->placeholder('e.g. From SM City, ride a tricycle going to Brgy. San Roque')
                                            ->columnSpanFull(),
                                        Forms\Components\TextInput::make('landmark')
                                            ->label('Landmark')
                                            ->maxLength(255)
                                            ->placeholder('e.g. Aling Nena\'s Sari-Sari Store'),
                                        Forms\Components\Select::make('transport_mode')
                                            ->label('Transport')
                                            ->options([
                                                'walk' => '🚶 Walk',
                                                'tricycle' => '🛺 Tricycle',
                                                'jeepney' => '🚌 Jeepney',
                                                'bus' => '🚍 Bus',
                                                'mrt' => '🚇 MRT / LRT',
                                                'drive' => '🚗 Drive',
                                                'grab' => '📱 Grab / Taxi',
                                            ])
                                            ->native(false),
                                        FileUpload::make('photo')
                                            ->label('Photo (optional)')
                                            ->image()
                                            ->disk('public')
                                            ->directory('properties/directions')
                                            ->maxSize(2048)
                                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp']),
                                    ])
                                    ->columns(3)
                                    ->orderable()
                                    ->collapsible()
                                    ->defaultItems(0)
                                    ->addActionLabel('Add Direction Step')
                                    ->columnSpanFull(),
                            ]),

                        // ── Rental Info Tab ────────────────────────────
                        Forms\Components\Tabs\Tab::make('Rental Info')
                            ->icon('heroicon-o-clipboard-document-list')
                            ->visible(fn (): bool => auth()->user()?->getStoreForPanel()?->template() === SectorTemplate::Rental)
                            ->schema([
                                Forms\Components\Section::make('Utility Inclusions')
                                    ->description('Let tenants know which utilities are included in the rent.')
                                    ->schema([
                                        Forms\Components\CheckboxList::make('utility_inclusions')
                                            ->label('')
                                            ->options([
                                                'water' => '💧 Water',
                                                'electricity' => '⚡ Electricity',
                                                'wifi' => '📶 WiFi / Internet',
                                                'cable_tv' => '📺 Cable TV',
                                                'gas' => '🔥 Cooking Gas',
                                                'trash' => '🗑️ Trash Collection',
                                                'laundry' => '👕 Shared Laundry',
                                                'parking' => '🅿️ Parking Space',
                                            ])
                                            ->columns(2)
                                            ->columnSpanFull(),
                                    ]),

                                Forms\Components\Section::make('House Rules')
                                    ->description('Set expectations for potential tenants.')
                                    ->schema([
                                        Forms\Components\CheckboxList::make('house_rules')
                                            ->label('')
                                            ->options([
                                                'no_pets' => '🐾 No Pets Allowed',
                                                'pets_allowed' => '🐕 Pets Allowed',
                                                'no_smoking' => '🚭 No Smoking Inside',
                                                'no_overnight_guests' => '🚫 No Overnight Guests',
                                                'guests_allowed' => '👥 Visitors / Guests Allowed',
                                                'curfew' => '🕐 Curfew (10 PM – 6 AM)',
                                                'no_cooking' => '🍳 No Cooking in Room',
                                                'cooking_allowed' => '🍲 Cooking Allowed',
                                                'quiet_hours' => '🔇 Quiet Hours (10 PM – 6 AM)',
                                                'id_required' => '🪪 Valid ID Required',
                                            ])
                                            ->columns(2)
                                            ->columnSpanFull(),
                                    ]),

                                Forms\Components\Section::make('Safety & Security')
                                    ->description('Highlight safety features to build tenant confidence.')
                                    ->schema([
                                        Forms\Components\CheckboxList::make('safety_features')
                                            ->label('')
                                            ->options([
                                                'cctv' => '📹 CCTV Cameras',
                                                'security_guard' => '💂 Security Guard',
                                                'gated' => '🚧 Gated Compound',
                                                'well_lit' => '💡 Well-Lit Pathways',
                                                'fire_extinguisher' => '🧯 Fire Extinguisher',
                                                'smoke_detector' => '🔔 Smoke Detector',
                                                'flood_free' => '🌊 Flood-Free Area',
                                                'backup_power' => '🔋 Backup Generator / Power',
                                            ])
                                            ->columns(2)
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        // ── Publishing Tab ─────────────────────────────
                        Forms\Components\Tabs\Tab::make('Publishing')
                            ->icon('heroicon-o-globe-alt')
                            ->schema([
                                Forms\Components\Toggle::make('is_featured')
                                    ->label('Featured Listing')
                                    ->helperText('Featured listings appear prominently on the browse page.'),

                                Forms\Components\DateTimePicker::make('published_at')
                                    ->label('Publish Date')
                                    ->helperText('Leave blank to publish immediately when status is set to Active.'),
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
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->tooltip(fn (Model $record): string => $record->title),

                Tables\Columns\TextColumn::make('property_type')
                    ->badge()
                    ->formatStateUsing(fn (PropertyType $state): string => $state->label())
                    ->color('primary')
                    ->sortable(),

                Tables\Columns\TextColumn::make('listing_type')
                    ->badge()
                    ->formatStateUsing(fn (ListingType $state): string => $state->label())
                    ->color(fn (ListingType $state): string => $state->color())
                    ->sortable(),

                Tables\Columns\TextColumn::make('price')
                    ->formatStateUsing(fn (Model $record): string => $record->formattedPrice())
                    ->sortable(),

                Tables\Columns\TextColumn::make('city')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (PropertyStatus $state): string => $state->label())
                    ->color(fn (PropertyStatus $state): string => $state->color())
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_featured')
                    ->boolean()
                    ->label('Featured')
                    ->sortable(),

                Tables\Columns\TextColumn::make('views_count')
                    ->label('Views')
                    ->sortable()
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('inquiries_count')
                    ->counts('inquiries')
                    ->label('Inquiries')
                    ->sortable()
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('avg_rating')
                    ->label('Rating')
                    ->formatStateUsing(function ($state, Model $record): string {
                        if (! $state) {
                            return '—';
                        }

                        $stars = str_repeat('★', (int) round((float) $state)).str_repeat('☆', 5 - (int) round((float) $state));

                        return $stars.' ('.$record->testimonials_count.')';
                    })
                    ->sortable()
                    ->color(fn ($state): string => match (true) {
                        ! $state => 'gray',
                        (float) $state >= 4.0 => 'success',
                        (float) $state >= 3.0 => 'warning',
                        default => 'danger',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('property_type')
                    ->options(collect(PropertyType::cases())->mapWithKeys(
                        fn (PropertyType $type) => [$type->value => $type->label()]
                    )),

                Tables\Filters\SelectFilter::make('listing_type')
                    ->options(collect(ListingType::cases())->mapWithKeys(
                        fn (ListingType $type) => [$type->value => $type->label()]
                    )),

                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(PropertyStatus::cases())->mapWithKeys(
                        fn (PropertyStatus $s) => [$s->value => $s->label()]
                    )),

                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('Featured'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('publish')
                    ->label('Publish')
                    ->icon('heroicon-o-globe-alt')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn (Model $record) => $record->publish())
                    ->visible(fn (Model $record): bool => $record->status === PropertyStatus::Draft),
                Tables\Actions\ReplicateAction::make()
                    ->label('Clone')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->excludeAttributes(['slug', 'published_at', 'views_count'])
                    ->mutateRecordDataUsing(function (array $data): array {
                        $data['title'] = '[Copy] '.$data['title'];
                        $data['slug'] = Str::slug($data['title']).'-'.Str::random(6);
                        $data['status'] = PropertyStatus::Draft->value;
                        $data['is_featured'] = false;

                        return $data;
                    })
                    ->successNotificationTitle('Property cloned as draft'),

                Tables\Actions\Action::make('mark_rented')
                    ->label('Mark as Rented')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->slideOver()
                    ->form([
                        Forms\Components\TextInput::make('tenant_name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('tenant_email')
                            ->email()
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('tenant_phone')
                            ->tel()
                            ->maxLength(30),

                        Forms\Components\TextInput::make('monthly_rent')
                            ->label('Monthly Rent (₱)')
                            ->required()
                            ->numeric()
                            ->minValue(1),

                        Forms\Components\TextInput::make('security_deposit')
                            ->label('Security Deposit (₱)')
                            ->numeric()
                            ->minValue(0),

                        Forms\Components\DatePicker::make('move_in_date')
                            ->required()
                            ->native(false)
                            ->default(now()),

                        Forms\Components\TextInput::make('lease_term_months')
                            ->label('Lease Term (months)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(120),

                        Forms\Components\Textarea::make('notes')
                            ->rows(2),
                    ])
                    ->action(function (Model $record, array $data): void {
                        $tenantUser = \App\Models\User::where('email', $data['tenant_email'])->first();

                        RentalAgreement::create([
                            'property_id' => $record->id,
                            'store_id' => $record->store_id,
                            'tenant_user_id' => $tenantUser?->id,
                            'tenant_name' => $data['tenant_name'],
                            'tenant_email' => $data['tenant_email'],
                            'tenant_phone' => $data['tenant_phone'] ?? null,
                            'monthly_rent' => (int) round($data['monthly_rent'] * 100),
                            'security_deposit' => isset($data['security_deposit'])
                                ? (int) round($data['security_deposit'] * 100)
                                : null,
                            'move_in_date' => $data['move_in_date'],
                            'lease_term_months' => $data['lease_term_months'] ?? null,
                            'notes' => $data['notes'] ?? null,
                        ]);

                        $record->update(['status' => \App\PropertyStatus::UnderOffer]);
                        // Only update inquiries that belong to this tenant, or actually close them all and let the RentalAgreement drive it?
                        // It's cleaner to keep them as "negotiating" if we want them to stick around, or just set to Closed.
                        $record->inquiries()->update(['status' => \App\InquiryStatus::Closed]);

                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Property is now Under Offer')
                            ->body('A rental agreement has been sent to the tenant for review and signature.')
                            ->send();
                    })
                    ->visible(fn (Model $record): bool => in_array($record->status, [
                        PropertyStatus::Active,
                        PropertyStatus::UnderOffer,
                    ], true) && in_array($record->listing_type, [
                        ListingType::ForRent,
                        ListingType::ForLease,
                    ], true)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulk_publish')
                        ->label('Publish Selected')
                        ->icon('heroicon-o-globe-alt')
                        ->color('success')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records): void {
                            $count = 0;
                            $records->each(function (Model $record) use (&$count): void {
                                if ($record->status === PropertyStatus::Draft) {
                                    $record->publish();
                                    $count++;
                                }
                            });

                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title("{$count} propert".($count === 1 ? 'y' : 'ies').' published')
                                ->send();
                        }),
                    Tables\Actions\BulkAction::make('bulk_archive')
                        ->label('Archive Selected')
                        ->icon('heroicon-o-archive-box')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records): void {
                            $count = 0;
                            $records->each(function (Model $record) use (&$count): void {
                                if ($record->status !== PropertyStatus::Archived) {
                                    $record->update(['status' => PropertyStatus::Archived]);
                                    $count++;
                                }
                            });

                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title("{$count} propert".($count === 1 ? 'y' : 'ies').' archived')
                                ->send();
                        }),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\TestimonialsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProperties::route('/'),
            'create' => Pages\CreateProperty::route('/create'),
            'edit' => Pages\EditProperty::route('/{record}/edit'),
        ];
    }
}
