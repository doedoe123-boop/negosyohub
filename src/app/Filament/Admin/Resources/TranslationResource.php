<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\TranslationResource\Pages;
use App\Models\Translation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Lunar\Models\Language;

class TranslationResource extends Resource
{
    protected static ?string $model = Translation::class;

    protected static ?string $navigationIcon = 'heroicon-o-language';

    protected static ?string $navigationGroup = 'Content';

    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Translation')
                    ->schema([
                        Forms\Components\Select::make('locale')
                            ->label('Language')
                            ->options(fn (): array => Language::query()
                                ->orderByDesc('default')
                                ->orderBy('name')
                                ->pluck('name', 'code')
                                ->all())
                            ->searchable()
                            ->required(),
                        Forms\Components\TextInput::make('key')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Example: checkout.payNow'),
                        Forms\Components\Textarea::make('value')
                            ->required()
                            ->rows(5)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Translation')
                    ->schema([
                        Infolists\Components\TextEntry::make('locale')
                            ->label('Language')
                            ->formatStateUsing(fn (string $state): string => Language::query()
                                ->where('code', $state)
                                ->value('name') ?? strtoupper($state)),
                        Infolists\Components\TextEntry::make('key'),
                        Infolists\Components\TextEntry::make('value')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('updated_at')
                            ->dateTime(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('locale')
                    ->label('Language')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Language::query()
                        ->where('code', $state)
                        ->value('name') ?? strtoupper($state)),
                Tables\Columns\TextColumn::make('key')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('value')
                    ->limit(80)
                    ->wrap(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('locale')
                    ->label('Language')
                    ->options(fn (): array => Language::query()
                        ->orderBy('name')
                        ->pluck('name', 'code')
                        ->all()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('key');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTranslations::route('/'),
            'create' => Pages\CreateTranslation::route('/create'),
            'view' => Pages\ViewTranslation::route('/{record}'),
            'edit' => Pages\EditTranslation::route('/{record}/edit'),
        ];
    }
}
