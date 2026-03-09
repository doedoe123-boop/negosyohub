<?php

namespace App\Filament\Admin\Resources\CampaignResource\RelationManagers;

use App\AdStatus;
use App\CouponScope;
use App\CouponType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class CouponsRelationManager extends RelationManager
{
    protected static string $relationship = 'coupons';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('code')
                    ->required()
                    ->maxLength(50)
                    ->unique(ignoreRecord: true),
                Forms\Components\Select::make('type')
                    ->options(collect(CouponType::cases())
                        ->mapWithKeys(fn (CouponType $t): array => [$t->value => $t->label()]))
                    ->required()
                    ->native(false),
                Forms\Components\Select::make('scope')
                    ->options(collect(CouponScope::cases())
                        ->mapWithKeys(fn (CouponScope $s): array => [$s->value => $s->label()]))
                    ->required()
                    ->native(false),
                Forms\Components\Select::make('status')
                    ->options(collect(AdStatus::cases())
                        ->mapWithKeys(fn (AdStatus $s): array => [$s->value => $s->label()]))
                    ->required()
                    ->default(AdStatus::Draft->value)
                    ->native(false),
                Forms\Components\TextInput::make('value')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('max_uses')
                    ->numeric()
                    ->minValue(1),
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
                Tables\Columns\TextColumn::make('code')
                    ->weight('bold')
                    ->copyable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof CouponType ? $state->label() : ucfirst((string) $state))
                    ->color('info'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof AdStatus ? $state->label() : ucfirst((string) $state))
                    ->color(fn ($state): string => $state instanceof AdStatus ? $state->color() : 'gray'),
                Tables\Columns\TextColumn::make('value'),
                Tables\Columns\TextColumn::make('times_used')
                    ->label('Used'),
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
