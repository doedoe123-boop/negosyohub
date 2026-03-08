<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PayoutResource\Pages;
use App\Models\Payout;
use App\Models\Store;
use App\PayoutMethod;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PayoutResource extends Resource
{
    protected static ?string $model = Payout::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Marketplace';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Payout Details')
                    ->schema([
                        Forms\Components\Select::make('store_id')
                            ->label('Store')
                            ->options(Store::query()->approved()->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                        Forms\Components\TextInput::make('amount')
                            ->numeric()
                            ->prefix('₱')
                            ->step(0.01)
                            ->minValue(0)
                            ->required(),
                        Forms\Components\DatePicker::make('period_start')
                            ->label('Period Start')
                            ->required(),
                        Forms\Components\DatePicker::make('period_end')
                            ->label('Period End')
                            ->afterOrEqual('period_start')
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->options([
                                Payout::STATUS_PENDING => 'Pending',
                                Payout::STATUS_PROCESSING => 'Processing',
                                Payout::STATUS_PAID => 'Paid',
                                Payout::STATUS_FAILED => 'Failed',
                            ])
                            ->required()
                            ->default(Payout::STATUS_PENDING)
                            ->native(false),
                        Forms\Components\TextInput::make('reference')
                            ->label('Reference / Transaction ID')
                            ->maxLength(255),
                        Forms\Components\DateTimePicker::make('paid_at')
                            ->label('Paid At')
                            ->visible(fn (Forms\Get $get): bool => $get('status') === Payout::STATUS_PAID),
                    ])->columns(2),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Payout Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('store.name')
                            ->label('Store'),
                        Infolists\Components\TextEntry::make('amount')
                            ->label('Amount')
                            ->money('PHP'),
                        Infolists\Components\TextEntry::make('period_start')
                            ->label('Period Start')
                            ->date(),
                        Infolists\Components\TextEntry::make('period_end')
                            ->label('Period End')
                            ->date(),
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                Payout::STATUS_PENDING => 'warning',
                                Payout::STATUS_PROCESSING => 'info',
                                Payout::STATUS_PAID => 'success',
                                Payout::STATUS_FAILED => 'danger',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('reference')
                            ->label('Reference')
                            ->default('—'),
                        Infolists\Components\TextEntry::make('paid_at')
                            ->label('Paid At')
                            ->formatStateUsing(fn ($state): string => $state ? $state->format('M d, Y H:i') : '—'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->dateTime(),
                    ])->columns(2),

                Infolists\Components\Section::make('Recipient Details')
                    ->description('Payout destination configured by the store owner.')
                    ->schema([
                        Infolists\Components\TextEntry::make('store.payout_method')
                            ->label('Payout Method')
                            ->formatStateUsing(fn ($state): string => $state instanceof PayoutMethod ? $state->label() : '—'),
                        Infolists\Components\TextEntry::make('store.payout_details.bank_name')
                            ->label('Bank Name')
                            ->default('—'),
                        Infolists\Components\TextEntry::make('store.payout_details.account_name')
                            ->label('Account Name')
                            ->default('—'),
                        Infolists\Components\TextEntry::make('store.payout_details.account_number')
                            ->label('Account Number')
                            ->default('—'),
                        Infolists\Components\TextEntry::make('store.payout_details.mobile_number')
                            ->label('Mobile Number')
                            ->default('—'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('store.name')
                    ->label('Store')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->money('PHP')
                    ->sortable(),
                Tables\Columns\TextColumn::make('period_start')
                    ->label('Period')
                    ->getStateUsing(fn (Payout $record): string => $record->period_start->format('M d').' – '.$record->period_end->format('M d, Y'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Payout::STATUS_PENDING => 'warning',
                        Payout::STATUS_PROCESSING => 'info',
                        Payout::STATUS_PAID => 'success',
                        Payout::STATUS_FAILED => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('reference')
                    ->default('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Paid At')
                    ->formatStateUsing(fn ($state): string => $state ? $state->format('M d, Y') : '—')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('store_id')
                    ->label('Store')
                    ->options(Store::query()->pluck('name', 'id'))
                    ->searchable(),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        Payout::STATUS_PENDING => 'Pending',
                        Payout::STATUS_PROCESSING => 'Processing',
                        Payout::STATUS_PAID => 'Paid',
                        Payout::STATUS_FAILED => 'Failed',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('markPaid')
                    ->label('Mark as Paid')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Mark Payout as Paid')
                    ->modalDescription('Confirm this payout has been successfully transferred to the store owner.')
                    ->form([
                        Forms\Components\TextInput::make('reference')
                            ->label('Transaction Reference')
                            ->placeholder('e.g. GCASH-123456 or BANK-REF-789')
                            ->required(),
                    ])
                    ->visible(fn (Payout $record): bool => $record->status !== Payout::STATUS_PAID)
                    ->action(function (Payout $record, array $data): void {
                        $record->update([
                            'status' => Payout::STATUS_PAID,
                            'reference' => $data['reference'],
                            'paid_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Payout marked as paid')
                            ->body('₱'.number_format($record->amount, 2)." payout to {$record->store->name} marked as paid.")
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayouts::route('/'),
            'create' => Pages\CreatePayout::route('/create'),
            'view' => Pages\ViewPayout::route('/{record}'),
            'edit' => Pages\EditPayout::route('/{record}/edit'),
        ];
    }
}
