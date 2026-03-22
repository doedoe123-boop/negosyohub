<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\LanguageResource\Pages;
use Awcodes\FilamentBadgeableColumn\Components\Badge;
use Awcodes\FilamentBadgeableColumn\Components\BadgeableColumn;
use Filament\Forms;
use Filament\Forms\Components\Component;
use Filament\Tables;
use Lunar\Admin\Filament\Resources\LanguageResource as BaseLanguageResource;
use Lunar\Models\Language;

class LanguageResource extends BaseLanguageResource
{
    protected static ?string $permission = null;

    protected static ?string $navigationGroup = 'E-commerce';

    protected static ?int $navigationSort = 13;

    protected static function getMainFormComponents(): array
    {
        return [
            static::getNameFormComponent(),
            static::getCodeFormComponent(),
            static::getDefaultFormComponent(),
            static::getActiveFormComponent(),
        ];
    }

    protected static function getActiveFormComponent(): Component
    {
        return Forms\Components\Toggle::make('is_active')
            ->label('Active')
            ->default(true)
            ->helperText('Inactive languages stay in the catalog but are hidden from the customer locale switcher.');
    }

    public static function getNavigationGroup(): ?string
    {
        return static::$navigationGroup;
    }

    protected static function getDefaultTable(Tables\Table $table): Tables\Table
    {
        return $table->columns([
            BadgeableColumn::make('name')
                ->separator('')
                ->suffixBadges([
                    Badge::make('default')
                        ->label('Default')
                        ->color('gray')
                        ->visible(fn (Language $record): bool => (bool) $record->default),
                    Badge::make('inactive')
                        ->label('Inactive')
                        ->color('danger')
                        ->visible(fn (Language $record): bool => ! $record->is_active),
                ])
                ->label('Name'),
            Tables\Columns\TextColumn::make('code')
                ->label('Code'),
            Tables\Columns\IconColumn::make('is_active')
                ->label('Active')
                ->boolean(),
        ]);
    }

    public static function getDefaultPages(): array
    {
        return [
            'index' => Pages\ListLanguages::route('/'),
            'create' => Pages\CreateLanguage::route('/create'),
            'edit' => Pages\EditLanguage::route('/{record}/edit'),
        ];
    }
}
