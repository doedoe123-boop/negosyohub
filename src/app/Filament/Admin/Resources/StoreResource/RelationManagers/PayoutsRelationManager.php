<?php

namespace App\Filament\Admin\Resources\StoreResource\RelationManagers;

use App\Models\Payout;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PayoutsRelationManager extends RelationManager
{
    protected static string $relationship = 'payouts';

    protected static ?string $title = 'Payouts';

    protected static ?string $icon = 'heroicon-o-banknotes';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
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
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
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
                    }),
                Tables\Columns\TextColumn::make('reference')
                    ->default('—'),
                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Paid At')
                    ->formatStateUsing(fn ($state): string => $state ? $state->format('M d, Y') : '—'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('markPaid')
                    ->label('Mark as Paid')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\TextInput::make('reference')
                            ->label('Transaction Reference')
                            ->required(),
                    ])
                    ->visible(fn (Payout $record): bool => $record->status !== Payout::STATUS_PAID)
                    ->action(function (Payout $record, array $data): void {
                        $record->update([
                            'status' => Payout::STATUS_PAID,
                            'reference' => $data['reference'],
                            'paid_at' => now(),
                        ]);

                        Notification::make()->title('Payout marked as paid')->success()->send();
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
