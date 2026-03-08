<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StoreResource\Pages;
use App\Mail\StoreReinstated;
use App\Mail\StoreSuspended;
use App\Models\Sector;
use App\Models\Store;
use App\Services\StoreService;
use App\StoreStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class StoreResource extends Resource
{
    protected static ?string $model = Store::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationGroup = 'Marketplace';

    protected static ?int $navigationSort = 1;

    /**
     * Only admins can access the store management resource.
     */
    public static function canAccess(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        return $user?->isAdmin() === true;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Store Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\Textarea::make('description')
                            ->maxLength(1000)
                            ->columnSpanFull(),
                        Forms\Components\Select::make('status')
                            ->options(collect(StoreStatus::cases())->mapWithKeys(
                                fn (StoreStatus $status) => [$status->value => ucfirst($status->value)]
                            ))
                            ->required(),
                        Forms\Components\Select::make('sector')
                            ->label('Industry Sector')
                            ->options(fn () => Sector::active()->pluck('name', 'slug')->toArray())
                            ->searchable(),
                        Forms\Components\TextInput::make('commission_rate')
                            ->numeric()
                            ->suffix('%')
                            ->default(15.00)
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01),
                    ])->columns(2),
                Forms\Components\Section::make('Owner')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship('owner', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(),
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
                Tables\Columns\TextColumn::make('owner.name')
                    ->label('Owner')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (StoreStatus $state): string => match ($state) {
                        StoreStatus::Pending => 'warning',
                        StoreStatus::Approved => 'success',
                        StoreStatus::Suspended => 'danger',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('sector')
                    ->label('Sector')
                    ->badge()
                    ->formatStateUsing(function (?string $state): string {
                        return Sector::where('slug', $state)->value('name') ?? ($state ?? '—');
                    })
                    ->color(function (?string $state): string {
                        $colorMap = ['indigo' => 'info', 'emerald' => 'success', 'amber' => 'warning', 'rose' => 'danger', 'sky' => 'info', 'violet' => 'info'];
                        $color = Sector::where('slug', $state)->value('color');

                        return $colorMap[$color] ?? 'gray';
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('commission_rate')
                    ->suffix('%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(StoreStatus::cases())->mapWithKeys(
                        fn (StoreStatus $status) => [$status->value => ucfirst($status->value)]
                    )),
                Tables\Filters\SelectFilter::make('sector')
                    ->label('Industry Sector')
                    ->options(fn () => Sector::active()->pluck('name', 'slug')->toArray()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Store $record): bool => $record->status !== StoreStatus::Approved)
                    ->action(fn (Store $record) => $record->update(['status' => StoreStatus::Approved])),
                Tables\Actions\Action::make('suspend')
                    ->label('Suspend')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Store $record): bool => $record->status !== StoreStatus::Suspended)
                    ->form([
                        Forms\Components\Select::make('reason_category')
                            ->label('Reason Category')
                            ->options([
                                'Terms Violation' => 'Terms Violation',
                                'Fraud / Misrepresentation' => 'Fraud / Misrepresentation',
                                'Documentation Issue' => 'Documentation Issue',
                                'Inactive / Unresponsive' => 'Inactive / Unresponsive',
                                'Customer Complaints' => 'Customer Complaints',
                                'Other' => 'Other',
                            ])
                            ->required(),
                        Forms\Components\Textarea::make('reason_details')
                            ->label('Additional Details')
                            ->placeholder('Provide details about the suspension reason...')
                            ->maxLength(1000),
                    ])
                    ->modalHeading('Suspend Store')
                    ->modalDescription('This will immediately suspend the store and notify the owner via email.')
                    ->modalSubmitActionLabel('Suspend Store')
                    ->action(function (Store $record, array $data): void {
                        $reason = $data['reason_category'];
                        if (! empty($data['reason_details'])) {
                            $reason .= ': '.$data['reason_details'];
                        }

                        app(StoreService::class)->suspend($record, $reason);

                        Mail::to($record->owner->email)->send(new StoreSuspended($record->refresh(), $reason));

                        Notification::make()
                            ->title('Store Suspended')
                            ->body("Store '{$record->name}' has been suspended and the owner has been notified.")
                            ->danger()
                            ->send();
                    }),
                Tables\Actions\Action::make('reinstate')
                    ->label('Reinstate')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Reinstate Store')
                    ->modalDescription('This will reactivate the store and notify the owner via email. The store will become visible to customers again.')
                    ->modalSubmitActionLabel('Reinstate Store')
                    ->visible(fn (Store $record): bool => $record->isSuspended())
                    ->action(function (Store $record): void {
                        app(StoreService::class)->reinstate($record);

                        Mail::to($record->owner->email)->send(new StoreReinstated($record->refresh()));

                        Notification::make()
                            ->title('Store Reinstated')
                            ->body("Store '{$record->name}' has been reinstated and the owner has been notified.")
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->groups([
                Tables\Grouping\Group::make('sector')
                    ->label('Industry Sector')
                    ->getTitleFromRecordUsing(fn (Store $record): string => $record->sector?->label() ?? 'Unclassified'),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStores::route('/'),
            'create' => Pages\CreateStore::route('/create'),
            'view' => Pages\ViewStore::route('/{record}'),
            'edit' => Pages\EditStore::route('/{record}/edit'),
        ];
    }
}
