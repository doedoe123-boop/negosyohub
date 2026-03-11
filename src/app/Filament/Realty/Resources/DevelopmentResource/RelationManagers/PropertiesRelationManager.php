<?php

namespace App\Filament\Realty\Resources\DevelopmentResource\RelationManagers;

use App\ListingType;
use App\PropertyStatus;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PropertiesRelationManager extends RelationManager
{
    protected static string $relationship = 'properties';

    protected static ?string $title = 'Properties';

    protected static ?string $icon = 'heroicon-o-home-modern';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(40),

                Tables\Columns\TextColumn::make('unit_number')
                    ->label('Unit')
                    ->placeholder('—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('property_type')
                    ->label('Type')
                    ->badge(),

                Tables\Columns\TextColumn::make('listing_type')
                    ->label('Listing')
                    ->badge()
                    ->formatStateUsing(fn (ListingType $state): string => $state->label())
                    ->color(fn (ListingType $state): string => $state->color()),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (PropertyStatus $state): string => $state->label())
                    ->color(fn (PropertyStatus $state): string => $state->color()),

                Tables\Columns\TextColumn::make('price')
                    ->money('PHP')
                    ->sortable(),

                Tables\Columns\TextColumn::make('bedrooms')
                    ->label('Beds')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('floor_area')
                    ->label('Area (sqm)')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(PropertyStatus::cases())->mapWithKeys(
                        fn (PropertyStatus $s) => [$s->value => $s->label()]
                    )),

                Tables\Filters\SelectFilter::make('listing_type')
                    ->options(collect(ListingType::cases())->mapWithKeys(
                        fn (ListingType $t) => [$t->value => $t->label()]
                    )),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => route('filament.realty.resources.properties.edit', $record))
                    ->openUrlInNewTab(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
