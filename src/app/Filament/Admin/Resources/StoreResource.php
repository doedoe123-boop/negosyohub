<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\StoreResource\Pages;
use App\Mail\StoreApproved;
use App\Mail\StoreReinstated;
use App\Mail\StoreSuspended;
use App\Models\Sector;
use App\Models\Store;
use App\Notifications\SellerEmailVerificationNotification;
use App\PhilippineIdType;
use App\Services\StoreService;
use App\StoreStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class StoreResource extends Resource
{
    protected static ?string $model = Store::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationGroup = 'Marketplace';

    protected static ?int $navigationSort = 1;

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
                            ->options(
                                Sector::all()->pluck('name', 'slug')
                            )
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
                Forms\Components\Section::make('Verification Documents')
                    ->schema([
                        Forms\Components\TextInput::make('id_type')
                            ->label('ID Type')
                            ->formatStateUsing(fn (?string $state): string => PhilippineIdType::tryFrom($state ?? '')?->label() ?? ($state ?? '—'))
                            ->disabled(),
                        Forms\Components\TextInput::make('id_number')
                            ->label('ID Number')
                            ->disabled(),
                        Forms\Components\Placeholder::make('business_permit_link')
                            ->label('Business Permit')
                            ->content(function (?Store $record): string {
                                if (! $record?->business_permit) {
                                    return 'No document uploaded';
                                }

                                return $record->business_permit;
                            }),
                    ])->columns(2)
                    ->collapsible(),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Store Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('name'),
                        Infolists\Components\TextEntry::make('slug'),
                        Infolists\Components\TextEntry::make('description')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn (StoreStatus $state): string => match ($state) {
                                StoreStatus::Pending => 'warning',
                                StoreStatus::Approved => 'success',
                                StoreStatus::Rejected => 'danger',
                                StoreStatus::Suspended => 'danger',
                            }),
                        Infolists\Components\TextEntry::make('sector')
                            ->label('Industry Sector')
                            ->badge()
                            ->formatStateUsing(function (?string $state): string {
                                return Sector::where('slug', $state)->value('name') ?? $state ?? '—';
                            })
                            ->color(function (?string $state): string {
                                return Sector::where('slug', $state)->value('color') ?? 'gray';
                            }),
                        Infolists\Components\TextEntry::make('commission_rate')
                            ->suffix('%'),
                    ])->columns(2),
                Infolists\Components\Section::make('Owner')
                    ->schema([
                        Infolists\Components\TextEntry::make('owner.name')
                            ->label('Name'),
                        Infolists\Components\TextEntry::make('owner.email')
                            ->label('Email'),
                        Infolists\Components\TextEntry::make('owner.phone')
                            ->label('Phone')
                            ->default('—'),
                    ])->columns(3),
                Infolists\Components\Section::make('Address')
                    ->schema([
                        Infolists\Components\TextEntry::make('address.line_one')
                            ->label('Street'),
                        Infolists\Components\TextEntry::make('address.city')
                            ->label('City'),
                        Infolists\Components\TextEntry::make('address.postcode')
                            ->label('Postcode'),
                    ])->columns(3),
                Infolists\Components\Section::make('Verification Documents')
                    ->schema([
                        Infolists\Components\TextEntry::make('id_type')
                            ->label('ID Type')
                            ->formatStateUsing(fn (?string $state): string => PhilippineIdType::tryFrom($state ?? '')?->label() ?? '—'),
                        Infolists\Components\TextEntry::make('id_number')
                            ->label('ID Number')
                            ->default('—'),
                        Infolists\Components\ViewEntry::make('business_permit_preview')
                            ->label('Business Permit')
                            ->view('filament.infolists.document-preview')
                            ->viewData(fn (Store $record): array => [
                                'url' => $record->business_permit
                                    ? route('admin.stores.document.preview', ['store' => $record, 'field' => 'business_permit'])
                                    : null,
                                'downloadUrl' => $record->business_permit
                                    ? route('admin.stores.document', ['store' => $record, 'field' => 'business_permit'])
                                    : null,
                                'type' => $record->business_permit
                                    ? strtolower(pathinfo($record->business_permit, PATHINFO_EXTENSION))
                                    : null,
                                'label' => $record->business_permit
                                    ? basename($record->business_permit)
                                    : 'No document uploaded',
                            ])
                            ->columnSpanFull(),
                    ])->columns(2),
                Infolists\Components\Section::make('Compliance Documents')
                    ->schema(function (Store $record): array {
                        $docs = $record->compliance_documents ?? [];

                        if (empty($docs)) {
                            return [
                                Infolists\Components\TextEntry::make('no_compliance_docs')
                                    ->label('')
                                    ->default('No compliance documents uploaded.')
                                    ->columnSpanFull(),
                            ];
                        }

                        $entries = [];

                        foreach ($docs as $key => $doc) {
                            $ext = strtolower(pathinfo($doc['path'] ?? '', PATHINFO_EXTENSION));

                            $entries[] = Infolists\Components\ViewEntry::make("compliance_doc_{$key}")
                                ->label($doc['label'] ?? $key)
                                ->view('filament.infolists.document-preview')
                                ->viewData([
                                    'url' => route('admin.stores.compliance.preview', ['store' => $record, 'key' => $key]),
                                    'downloadUrl' => route('admin.stores.compliance.preview', ['store' => $record, 'key' => $key]),
                                    'type' => $ext,
                                    'label' => basename($doc['path'] ?? ''),
                                    'required' => $doc['required'] ?? false,
                                ]);
                        }

                        return $entries;
                    })
                    ->columns(2)
                    ->icon('heroicon-o-shield-check')
                    ->iconColor('info')
                    ->visible(fn (Store $record): bool => ! empty($record->compliance_documents)),
                Infolists\Components\Section::make('Suspension Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('suspension_reason')
                            ->label('Reason')
                            ->default('—'),
                        Infolists\Components\TextEntry::make('suspended_at')
                            ->label('Suspended At')
                            ->dateTime()
                            ->placeholder('—'),
                    ])->columns(2)
                    ->icon('heroicon-o-exclamation-triangle')
                    ->iconColor('danger')
                    ->visible(fn (Store $record): bool => $record->isSuspended()),
                Infolists\Components\Section::make('Timestamps')
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('updated_at')
                            ->dateTime(),
                    ])->columns(2)
                    ->collapsed(),
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
                Tables\Columns\TextColumn::make('owner.email')
                    ->label('Owner Email')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (StoreStatus $state): string => match ($state) {
                        StoreStatus::Pending => 'warning',
                        StoreStatus::Approved => 'success',
                        StoreStatus::Rejected => 'danger',
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
                    ->action(function (Store $record): void {
                        $record->update(['status' => StoreStatus::Approved]);
                        $record->generateLoginToken();

                        $ownerEmail = $record->owner->email;

                        Log::info('Store approval started', [
                            'store_id' => $record->id,
                            'store_name' => $record->name,
                            'owner_email' => $ownerEmail,
                        ]);

                        try {
                            if (! $record->owner->hasVerifiedEmail()) {
                                $record->owner->notify(new SellerEmailVerificationNotification);
                            }

                            Mail::to($ownerEmail)->send(new StoreApproved($record));

                            Log::info('Store approval email sent successfully', [
                                'store_id' => $record->id,
                                'owner_email' => $ownerEmail,
                            ]);
                        } catch (\Throwable $e) {
                            Log::error('Store approval email failed', [
                                'store_id' => $record->id,
                                'owner_email' => $ownerEmail,
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString(),
                            ]);

                            Notification::make()
                                ->title('Store approved but email failed')
                                ->body("Error: {$e->getMessage()}")
                                ->danger()
                                ->persistent()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Store approved')
                            ->body("Approval email sent to {$ownerEmail}")
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (Store $record): bool => $record->status === StoreStatus::Pending)
                    ->form([
                        Forms\Components\Select::make('reason_category')
                            ->label('Reason Category')
                            ->options([
                                'Incomplete Documentation' => 'Incomplete Documentation',
                                'Invalid ID / Verification' => 'Invalid ID / Verification',
                                'Business Permit Issue' => 'Business Permit Issue',
                                'Duplicate Application' => 'Duplicate Application',
                                'Policy Violation' => 'Policy Violation',
                                'Other' => 'Other',
                            ])
                            ->required(),
                        Forms\Components\Textarea::make('reason_details')
                            ->label('Additional Details')
                            ->placeholder('Provide details about the rejection reason...')
                            ->maxLength(1000),
                    ])
                    ->modalHeading('Reject Store Application')
                    ->modalDescription('This will reject the store application and notify the owner via email.')
                    ->modalSubmitActionLabel('Reject Application')
                    ->action(function (Store $record, array $data): void {
                        $reason = $data['reason_category'];
                        if (! empty($data['reason_details'])) {
                            $reason .= ': '.$data['reason_details'];
                        }

                        app(StoreService::class)->reject($record, $reason);

                        Notification::make()
                            ->title('Store Rejected')
                            ->body("Store '{$record->name}' has been rejected and the owner has been notified.")
                            ->danger()
                            ->send();
                    }),
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
                    Tables\Actions\BulkAction::make('bulk_approve')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Bulk Approve Stores')
                        ->modalDescription('This will approve all selected pending stores and send approval emails to each owner.')
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records): void {
                            $approved = 0;

                            foreach ($records as $record) {
                                if ($record->status === StoreStatus::Pending) {
                                    $record->update(['status' => StoreStatus::Approved]);
                                    $record->generateLoginToken();

                                    try {
                                        Mail::to($record->owner->email)->send(new StoreApproved($record));
                                    } catch (\Throwable) {
                                        // Continue processing remaining stores
                                    }

                                    $approved++;
                                }
                            }

                            Notification::make()
                                ->title("{$approved} store(s) approved")
                                ->body('Approval emails have been sent to each store owner.')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->groups([
                Tables\Grouping\Group::make('sector')
                    ->label('Industry Sector')
                    ->getTitleFromRecordUsing(fn (Store $record): string => Sector::where('slug', $record->sector)->value('name') ?? $record->sector ?? 'Unclassified'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            StoreResource\RelationManagers\StaffRelationManager::class,
            StoreResource\RelationManagers\OrdersRelationManager::class,
            StoreResource\RelationManagers\PayoutsRelationManager::class,
        ];
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
