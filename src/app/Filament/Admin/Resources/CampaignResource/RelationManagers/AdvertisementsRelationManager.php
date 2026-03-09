<?php

namespace App\Filament\Admin\Resources\CampaignResource\RelationManagers;

use App\AdPlacement;
use App\AdStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class AdvertisementsRelationManager extends RelationManager
{
    protected static string $relationship = 'advertisements';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('placement')
                    ->options(collect(AdPlacement::cases())
                        ->mapWithKeys(fn (AdPlacement $p): array => [$p->value => $p->label()]))
                    ->required()
                    ->native(false),
                Forms\Components\Select::make('status')
                    ->options(collect(AdStatus::cases())
                        ->mapWithKeys(fn (AdStatus $s): array => [$s->value => $s->label()]))
                    ->required()
                    ->default(AdStatus::Draft->value)
                    ->native(false),
                Forms\Components\TextInput::make('priority')
                    ->numeric()
                    ->default(0),
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
                Tables\Columns\TextColumn::make('title')
                    ->limit(30),
                Tables\Columns\TextColumn::make('placement')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof AdPlacement ? $state->label() : ucfirst((string) $state))
                    ->color('primary'),
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
