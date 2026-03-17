<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\StaffResource\Pages;
use App\Filament\Admin\Resources\StaffResource\RelationManagers;
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;

class StaffResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Administration';

    protected static ?string $navigationLabel = 'Staff Management';

    protected static ?string $modelLabel = 'Staff Member';

    protected static ?string $pluralModelLabel = 'Staff';

    protected static ?string $slug = 'staff';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getEloquentQuery()->count() ?: null;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->where('role', UserRole::Admin);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Account Information')
                    ->description('Basic account details for the staff member.')
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
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Hash::make($state) : null)
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->minLength(8)
                            ->helperText(fn (string $operation): string => $operation === 'edit'
                                ? 'Leave blank to keep existing password.'
                                : 'Minimum 8 characters.'),
                    ])->columns(2),
                Forms\Components\Section::make('Roles & Permissions')
                    ->description('Assign one or more Spatie roles to control access across the admin panel.')
                    ->schema([
                        Forms\Components\CheckboxList::make('roles')
                            ->relationship(
                                'roles',
                                'name',
                                fn (Builder $query) => $query
                                    ->where('guard_name', 'web')
                                    ->whereIn('name', [
                                        'super_admin',
                                        'manager',
                                        'support',
                                        'moderator',
                                        'finance',
                                        'admin',
                                        'staff',
                                    ])
                            )
                            ->descriptions(function (): array {
                                return \Spatie\Permission\Models\Role::where('guard_name', 'web')
                                    ->whereIn('name', ['super_admin', 'manager', 'support', 'moderator', 'finance', 'admin', 'staff'])
                                    ->get()
                                    ->mapWithKeys(fn ($role) => [$role->id => match ($role->name) {
                                        'super_admin' => 'Full system access — all resources and settings',
                                        'manager' => 'Manage stores, orders, payouts, and users',
                                        'support' => 'Handle support tickets and customer inquiries',
                                        'moderator' => 'Moderate content, reviews, and listed stores',
                                        'finance' => 'Access payouts, commissions, and revenue reports',
                                        'admin' => 'Standard admin panel access with management capabilities',
                                        'staff' => 'Read-only assistive access to the admin panel',
                                        default => '',
                                    }])
                                    ->toArray();
                            })
                            ->columns(3)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Direct Permissions')
                    ->description('Grant individual permissions beyond what the assigned roles already provide.')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Forms\Components\CheckboxList::make('permissions')
                            ->options(fn (): array => \Spatie\Permission\Models\Permission::where('guard_name', 'web')
                                ->orderBy('name')
                                ->pluck('name', 'name')
                                ->toArray())
                            ->descriptions([
                                'manage-users' => 'Create, edit, and deactivate user accounts',
                                'manage-marketing' => 'Manage promotions, banners, and campaigns',
                                'manage-legal' => 'Update terms, privacy policy, and legal documents',
                                'manage-platform' => 'Configure platform settings and system options',
                                'manage-marketplace' => 'Oversee stores, listings, and marketplace rules',
                                'manage-tickets' => 'Handle support tickets and customer inquiries',
                                'manage-ecommerce' => 'Manage orders, payouts, and commissions',
                                'manage-content' => 'Moderate content, FAQs, and announcements',
                            ])
                            ->afterStateHydrated(function (Forms\Components\CheckboxList $component, ?Model $record): void {
                                if ($record) {
                                    $component->state($record->getDirectPermissions()->pluck('name')->toArray());
                                }
                            })
                            ->saveRelationshipsUsing(function (Model $record, array $state): void {
                                $record->syncPermissions($state);
                            })
                            ->columns(2)
                            ->columnSpanFull()
                            ->searchable()
                            ->noSearchResultsMessage('No permissions found.')
                            ->helperText('These supplement but do not override role-based permissions.'),
                    ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Group::make()
                    ->schema([
                        Infolists\Components\Section::make('Account Details')
                            ->schema([
                                Infolists\Components\TextEntry::make('name')
                                    ->weight('bold')
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large),
                                Infolists\Components\TextEntry::make('email')
                                    ->icon('heroicon-o-envelope')
                                    ->copyable(),
                                Infolists\Components\TextEntry::make('phone')
                                    ->icon('heroicon-o-phone')
                                    ->default('—'),
                                Infolists\Components\TextEntry::make('email_verified_at')
                                    ->label('Email Verified')
                                    ->dateTime('M d, Y H:i')
                                    ->placeholder('Not verified')
                                    ->icon('heroicon-o-shield-check'),
                            ])->columns(2),

                        Infolists\Components\Section::make('Assigned Roles')
                            ->schema([
                                Infolists\Components\TextEntry::make('roles.name')
                                    ->label('')
                                    ->badge()
                                    ->color('info')
                                    ->separator(',')
                                    ->default('No roles assigned'),
                            ]),

                        Infolists\Components\Section::make('Account Status')
                            ->schema([
                                Infolists\Components\TextEntry::make('deleted_at')
                                    ->label('Status')
                                    ->formatStateUsing(fn ($state): string => $state ? 'Disabled' : 'Active')
                                    ->badge()
                                    ->color(fn ($state): string => $state ? 'danger' : 'success'),
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Account Created')
                                    ->dateTime(),
                                Infolists\Components\TextEntry::make('updated_at')
                                    ->label('Last Updated')
                                    ->since(),
                            ])->columns(3),
                    ])->columnSpan(2),

                Infolists\Components\Group::make()
                    ->schema([
                        Infolists\Components\Section::make('Activity Snapshot')
                            ->schema([
                                Infolists\Components\TextEntry::make('latestLogin.created_at')
                                    ->label('Last Login')
                                    ->dateTime('M d, Y H:i')
                                    ->placeholder('Never'),
                                Infolists\Components\TextEntry::make('latestLogin.ip_address')
                                    ->label('Last IP')
                                    ->placeholder('—'),
                                Infolists\Components\TextEntry::make('latestLogin.status')
                                    ->label('Login Status')
                                    ->badge()
                                    ->color(fn (?string $state): string => $state === 'success' ? 'success' : 'danger')
                                    ->formatStateUsing(fn (?string $state): string => $state ? ucfirst($state) : '—'),
                            ]),
                    ])->columnSpan(1),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->icon('heroicon-o-envelope'),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->color('info')
                    ->separator(','),
                Tables\Columns\IconColumn::make('deleted_at')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->getStateUsing(fn (User $record): bool => ! $record->trashed()),
                Tables\Columns\TextColumn::make('latestLogin.created_at')
                    ->label('Last Login')
                    ->since()
                    ->placeholder('Never')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Joined')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->relationship('roles', 'name', fn (Builder $query) => $query->where('guard_name', 'web'))
                    ->multiple()
                    ->preload(),
                Tables\Filters\TernaryFilter::make('active')
                    ->label('Account Status')
                    ->placeholder('All')
                    ->trueLabel('Active Only')
                    ->falseLabel('Disabled Only')
                    ->queries(
                        true: fn (Builder $q) => $q->whereNull('deleted_at'),
                        false: fn (Builder $q) => $q->whereNotNull('deleted_at'),
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('resetPassword')
                        ->label('Reset Password')
                        ->icon('heroicon-o-key')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->form([
                            Forms\Components\TextInput::make('new_password')
                                ->label('New Password')
                                ->password()
                                ->revealable()
                                ->required()
                                ->minLength(8),
                        ])
                        ->action(function (User $record, array $data): void {
                            $record->update(['password' => Hash::make($data['new_password'])]);

                            activity()
                                ->performedOn($record)
                                ->causedBy(auth()->user())
                                ->withProperties(['action' => 'password_reset'])
                                ->log('Password was reset by admin');

                            Notification::make()
                                ->title('Password Reset')
                                ->body("Password for {$record->name} has been reset successfully.")
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\Action::make('deactivate')
                        ->label('Deactivate')
                        ->icon('heroicon-o-no-symbol')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Deactivate Staff Account')
                        ->modalDescription('This will immediately revoke access. The account can be reactivated later.')
                        ->visible(fn (User $record): bool => ! $record->trashed())
                        ->action(function (User $record): void {
                            $record->delete();

                            activity()
                                ->performedOn($record)
                                ->causedBy(auth()->user())
                                ->withProperties(['action' => 'deactivated'])
                                ->log('Account deactivated');

                            Notification::make()
                                ->title('Staff Deactivated')
                                ->body("{$record->name}'s account has been deactivated.")
                                ->warning()
                                ->send();
                        }),
                    Tables\Actions\Action::make('reactivate')
                        ->label('Reactivate')
                        ->icon('heroicon-o-arrow-path')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (User $record): bool => $record->trashed())
                        ->action(function (User $record): void {
                            $record->restore();

                            activity()
                                ->performedOn($record)
                                ->causedBy(auth()->user())
                                ->withProperties(['action' => 'reactivated'])
                                ->log('Account reactivated');

                            Notification::make()
                                ->title('Staff Reactivated')
                                ->body("{$record->name}'s account has been reactivated.")
                                ->success()
                                ->send();
                        }),
                ])->tooltip('More Actions'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Deactivate Selected'),
                    Tables\Actions\RestoreBulkAction::make()
                        ->label('Reactivate Selected'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No Staff Members')
            ->emptyStateDescription('Create your first staff account to get started.')
            ->emptyStateIcon('heroicon-o-user-group');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\LoginHistoryRelationManager::class,
            RelationManagers\ActivityLogRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStaff::route('/'),
            'create' => Pages\CreateStaff::route('/create'),
            'view' => Pages\ViewStaff::route('/{record}'),
            'edit' => Pages\EditStaff::route('/{record}/edit'),
        ];
    }
}
