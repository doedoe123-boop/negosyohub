<?php

namespace App\Filament\Realty\Resources;

use App\Filament\Realty\Resources\RentalAgreementResource\Pages;
use App\Models\RentalAgreement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RentalAgreementResource extends Resource
{
    protected static ?string $model = RentalAgreement::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Rentals';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Rental Agreement';

    protected static ?string $pluralModelLabel = 'Rental Agreements';

    public static function getEloquentQuery(): Builder
    {
        $store = auth()->user()?->getStoreForPanel();

        return parent::getEloquentQuery()
            ->where('store_id', $store?->id)
            ->with(['property', 'tenantUser']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Tenant Information')
                    ->schema([
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
                    ])->columns(3),

                Forms\Components\Section::make('Agreement Details')
                    ->schema([
                        Forms\Components\Select::make('property_id')
                            ->label('Property')
                            ->relationship('property', 'title', fn (Builder $query) => $query->where(
                                'store_id',
                                auth()->user()?->getStoreForPanel()?->id
                            ))
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\DatePicker::make('move_in_date')
                            ->required()
                            ->native(false),

                        Forms\Components\TextInput::make('monthly_rent')
                            ->label('Monthly Rent (₱)')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->helperText('Enter amount in pesos. Stored internally as centavos.'),

                        Forms\Components\TextInput::make('security_deposit')
                            ->label('Security Deposit (₱)')
                            ->numeric()
                            ->minValue(0),

                        Forms\Components\TextInput::make('lease_term_months')
                            ->label('Lease Term (months)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(120),

                        Forms\Components\Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Status & Tenant Responses')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending Review',
                                'negotiating' => 'Negotiating',
                                'signed' => 'Signed',
                            ])
                            ->required(),

                        Forms\Components\DateTimePicker::make('signed_at')
                            ->label('Signed At')
                            ->disabled(),

                        Forms\Components\Textarea::make('tenant_questions')
                            ->label('Questions from Tenant')
                            ->helperText('If the tenant has questions during review, they will appear here.')
                            ->rows(2)
                            ->disabled()
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('landlord_response')
                            ->label('Your Response')
                            ->helperText('Answer the tenant\'s questions or provide clarifications.')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tenant_name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('tenant_email')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('property.title')
                    ->label('Property')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'negotiating' => 'warning',
                        'signed' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                Tables\Columns\TextColumn::make('monthly_rent')
                    ->label('Monthly Rent')
                    ->formatStateUsing(fn (int $state): string => '₱'.number_format($state / 100, 2)),

                Tables\Columns\TextColumn::make('move_in_date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('lease_term_months')
                    ->label('Lease (months)')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRentalAgreements::route('/'),
            'create' => Pages\CreateRentalAgreement::route('/create'),
            'view' => Pages\ViewRentalAgreement::route('/{record}'),
            'edit' => Pages\EditRentalAgreement::route('/{record}/edit'),
        ];
    }
}
