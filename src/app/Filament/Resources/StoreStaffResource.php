<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StoreStaffResource\Pages;
use App\Models\User;
use App\UserRole;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class StoreStaffResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Store Management';

    protected static ?string $navigationLabel = 'Staff';

    protected static ?string $modelLabel = 'Staff Member';

    protected static ?string $pluralModelLabel = 'Staff Members';

    protected static ?string $slug = 'store-staff';

    protected static ?int $navigationSort = 2;

    /**
     * Only store owners can manage staff.
     */
    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user?->isStoreOwner() === true;
    }

    /**
     * Bypass UserPolicy — store owners can always create staff for their own store.
     */
    public static function canCreate(): bool
    {
        return Auth::user()?->isStoreOwner() === true;
    }

    /**
     * Bypass UserPolicy — store owners can edit staff belonging to their store.
     */
    public static function canEdit(Model $record): bool
    {
        /** @var User $user */
        $user = Auth::user();

        return $user->isStoreOwner() && $record->store_id === $user->store?->id;
    }

    /**
     * Bypass UserPolicy — store owners can delete staff belonging to their store.
     */
    public static function canDelete(Model $record): bool
    {
        /** @var User $user */
        $user = Auth::user();

        return $user->isStoreOwner() && $record->store_id === $user->store?->id;
    }

    /**
     * Scope the query to only show staff belonging to the current store owner's store.
     */
    public static function getEloquentQuery(): Builder
    {
        /** @var User $user */
        $user = Auth::user();

        return parent::getEloquentQuery()
            ->where('role', UserRole::Staff)
            ->where('store_id', $user->store?->id);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Staff Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Full Name')
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
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Hash::make($state) : null)
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->minLength(8)
                            ->maxLength(255)
                            ->helperText(fn (string $operation): ?string => $operation === 'edit' ? 'Leave blank to keep current password.' : null),
                    ])->columns(2),

                Forms\Components\Section::make('Permissions')
                    ->description('Choose which areas this staff member can access.')
                    ->schema([
                        Forms\Components\CheckboxList::make('staff_permissions')
                            ->label('')
                            ->options([
                                'catalog:manage-products' => 'Manage Products',
                                'catalog:manage-collections' => 'Manage Collections',
                                'sales:manage-orders' => 'Manage Orders',
                                'sales:manage-customers' => 'Manage Customers',
                            ])
                            ->columns(2)
                            ->default([
                                'catalog:manage-products',
                                'catalog:manage-collections',
                                'sales:manage-orders',
                                'sales:manage-customers',
                            ])
                            ->afterStateHydrated(function (Forms\Components\CheckboxList $component, ?Model $record): void {
                                if ($record) {
                                    $component->state($record->getPermissionNames()->toArray());
                                }
                            })
                            ->dehydrated(false),
                    ]),
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

                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Joined')
                    ->dateTime('M d, Y')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No staff members yet')
            ->emptyStateDescription('Add staff members to help manage your store.')
            ->emptyStateIcon('heroicon-o-users');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStoreStaff::route('/'),
            'create' => Pages\CreateStoreStaff::route('/create'),
            'edit' => Pages\EditStoreStaff::route('/{record}/edit'),
        ];
    }
}
