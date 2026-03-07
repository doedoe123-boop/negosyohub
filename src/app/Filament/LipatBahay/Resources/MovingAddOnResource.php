<?php

namespace App\Filament\LipatBahay\Resources;

use App\Filament\LipatBahay\Resources\MovingAddOnResource\Pages;
use App\Models\MovingAddOn;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MovingAddOnResource extends Resource
{
    protected static ?string $model = MovingAddOn::class;

    protected static ?string $navigationIcon = 'heroicon-o-plus-circle';

    protected static ?string $navigationGroup = 'Services';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Add-On Service';

    protected static ?string $pluralModelLabel = 'Add-On Services';

    public static function getEloquentQuery(): Builder
    {
        $store = auth()->user()?->getStoreForPanel();

        return parent::getEloquentQuery()
            ->where('store_id', $store?->id);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Textarea::make('description')
                    ->maxLength(1000)
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('price')
                    ->label('Price (₱)')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->helperText('Enter amount in pesos. Stored internally as centavos.'),

                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->limit(60)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('price')
                    ->label('Price')
                    ->formatStateUsing(fn (int $state) => '₱'.number_format($state / 100, 2))
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
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
            'index' => Pages\ListMovingAddOns::route('/'),
            'create' => Pages\CreateMovingAddOn::route('/create'),
            'edit' => Pages\EditMovingAddOn::route('/{record}/edit'),
        ];
    }
}
