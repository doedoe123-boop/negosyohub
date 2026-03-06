<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\UserResource\Pages;
use App\Models\User;
use App\UserRole;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Marketplace';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('User Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(20),
                        Forms\Components\Select::make('role')
                            ->options(collect(UserRole::cases())->mapWithKeys(
                                fn (UserRole $role) => [$role->value => str($role->name)->headline()->toString()]
                            ))
                            ->required()
                            ->native(false),
                    ])->columns(2),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('User Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('name'),
                        Infolists\Components\TextEntry::make('email'),
                        Infolists\Components\TextEntry::make('phone')
                            ->default('—'),
                        Infolists\Components\TextEntry::make('role')
                            ->formatStateUsing(fn (?UserRole $state): string => $state ? str($state->name)->headline()->toString() : '—')
                            ->badge()
                            ->color(fn (?UserRole $state): string => match ($state) {
                                UserRole::Admin => 'danger',
                                UserRole::StoreOwner => 'warning',
                                UserRole::Staff => 'info',
                                UserRole::Customer => 'success',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('email_verified_at')
                            ->label('Email Verified')
                            ->formatStateUsing(fn ($state): string => $state ? \Carbon\Carbon::parse($state)->format('M d, Y H:i') : 'Not verified'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->dateTime(),
                    ])->columns(2),

                Infolists\Components\Section::make('Store')
                    ->schema([
                        Infolists\Components\TextEntry::make('store.name')
                            ->label('Owned Store')
                            ->default('—'),
                        Infolists\Components\TextEntry::make('assignedStore.name')
                            ->label('Assigned Store (Staff)')
                            ->default('—'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('role')
                    ->formatStateUsing(fn (?UserRole $state): string => $state ? str($state->name)->headline()->toString() : '—')
                    ->badge()
                    ->color(fn (?UserRole $state): string => match ($state) {
                        UserRole::Admin => 'danger',
                        UserRole::StoreOwner => 'warning',
                        UserRole::Staff => 'info',
                        UserRole::Customer => 'success',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('store.name')
                    ->label('Store')
                    ->default('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('email_verified_at')
                    ->label('Verified')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options(collect(UserRole::cases())->mapWithKeys(
                        fn (UserRole $role) => [$role->value => str($role->name)->headline()->toString()]
                    )),
                Tables\Filters\TernaryFilter::make('email_verified_at')
                    ->label('Email Verified')
                    ->nullable(),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('disable')
                    ->label('Disable')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Disable User Account')
                    ->modalDescription('The user will no longer be able to log in. You can restore the account later.')
                    ->visible(fn (User $record): bool => ! $record->trashed() && ! $record->isAdmin())
                    ->action(function (User $record): void {
                        $record->delete();

                        Notification::make()
                            ->title('User disabled')
                            ->body("{$record->name}'s account has been disabled.")
                            ->success()
                            ->send();
                    }),
                Tables\Actions\RestoreAction::make()
                    ->label('Enable')
                    ->icon('heroicon-o-arrow-path')
                    ->successNotificationTitle('User account re-enabled'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getRelations(): array
    {
        return [
            UserResource\RelationManagers\LoginHistoriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
