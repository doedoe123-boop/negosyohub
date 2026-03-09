<?php

namespace App\Filament\Admin\Resources\CampaignResource\RelationManagers;

use App\AdStatus;
use App\FeaturedType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class FeaturedListingsRelationManager extends RelationManager
{
    protected static string $relationship = 'featuredListings';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('featured_type')
                    ->label('Type')
                    ->options(collect(FeaturedType::cases())
                        ->mapWithKeys(fn (FeaturedType $t): array => [$t->value => $t->label()]))
                    ->required()
                    ->native(false),
                Forms\Components\Select::make('status')
                    ->options(collect(AdStatus::cases())
                        ->mapWithKeys(fn (AdStatus $s): array => [$s->value => $s->label()]))
                    ->required()
                    ->default(AdStatus::Draft->value)
                    ->native(false),
                Forms\Components\MorphToSelect::make('featurable')
                    ->label('Featured Item')
                    ->types([
                        Forms\Components\MorphToSelect\Type::make(\App\Models\Store::class)
                            ->titleAttribute('name'),
                    ])
                    ->searchable()
                    ->preload(),
                Forms\Components\TextInput::make('priority')
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('cost_cents')
                    ->label('Cost (cents)')
                    ->numeric()
                    ->minValue(0),
                Forms\Components\DateTimePicker::make('starts_at'),
                Forms\Components\DateTimePicker::make('ends_at')
                    ->afterOrEqual('starts_at'),
                Forms\Components\Hidden::make('created_by')
                    ->default(fn () => Auth::id()),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('featured_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof FeaturedType ? $state->label() : ucfirst((string) $state))
                    ->color('warning'),
                Tables\Columns\TextColumn::make('featurable_type')
                    ->label('Item')
                    ->formatStateUsing(fn (string $state): string => class_basename($state)),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof AdStatus ? $state->label() : ucfirst((string) $state))
                    ->color(fn ($state): string => $state instanceof AdStatus ? $state->color() : 'gray'),
                Tables\Columns\TextColumn::make('priority')
                    ->sortable(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
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
}
