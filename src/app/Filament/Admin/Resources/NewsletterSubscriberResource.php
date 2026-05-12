<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\NewsletterSubscriberResource\Pages;
use App\Models\NewsletterSubscriber;
use App\Services\BrevoNewsletterService;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class NewsletterSubscriberResource extends Resource
{
    protected static ?string $model = NewsletterSubscriber::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationGroup = 'Marketing';

    protected static ?string $navigationLabel = 'Newsletter Subscribers';

    protected static ?string $modelLabel = 'Newsletter Subscriber';

    protected static ?string $pluralModelLabel = 'Newsletter Subscribers';

    protected static ?int $navigationSort = 3;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Subscriber Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('email')
                            ->icon('heroicon-o-envelope')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('source')
                            ->badge()
                            ->default('website'),
                        Infolists\Components\TextEntry::make('brevo_sync_status')
                            ->label('Brevo Sync')
                            ->badge()
                            ->color(fn (?string $state): string => match ($state) {
                                'synced' => 'success',
                                'failed' => 'danger',
                                'skipped' => 'warning',
                                default => 'gray',
                            })
                            ->default('pending'),
                        Infolists\Components\TextEntry::make('welcome_email_status')
                            ->label('Welcome Email')
                            ->badge()
                            ->color(fn (?string $state): string => match ($state) {
                                'opened' => 'success',
                                'delivered', 'sent', 'resent' => 'info',
                                'failed', 'unsubscribed' => 'danger',
                                default => 'gray',
                            })
                            ->default('pending'),
                        Infolists\Components\TextEntry::make('subscribed_at')
                            ->label('Subscribed At')
                            ->dateTime('M d, Y h:i:s A')
                            ->default('—'),
                        Infolists\Components\TextEntry::make('brevo_contact_id')
                            ->label('Brevo Contact ID')
                            ->default('—'),
                        Infolists\Components\TextEntry::make('brevo_synced_at')
                            ->label('Brevo Synced At')
                            ->dateTime('M d, Y h:i:s A')
                            ->default('—'),
                        Infolists\Components\TextEntry::make('welcome_sent_at')
                            ->dateTime('M d, Y h:i:s A')
                            ->default('—'),
                        Infolists\Components\TextEntry::make('welcome_delivered_at')
                            ->dateTime('M d, Y h:i:s A')
                            ->default('—'),
                        Infolists\Components\TextEntry::make('welcome_opened_at')
                            ->dateTime('M d, Y h:i:s A')
                            ->default('—'),
                        Infolists\Components\TextEntry::make('welcome_bounced_at')
                            ->dateTime('M d, Y h:i:s A')
                            ->default('—'),
                        Infolists\Components\TextEntry::make('last_brevo_event')
                            ->default('—'),
                        Infolists\Components\TextEntry::make('last_brevo_error')
                            ->label('Last Brevo Error')
                            ->default('—')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Created At')
                            ->dateTime('M d, Y h:i:s A'),
                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Updated At')
                            ->dateTime('M d, Y h:i:s A'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('source')
                    ->badge()
                    ->searchable()
                    ->sortable()
                    ->default('website'),
                Tables\Columns\TextColumn::make('brevo_sync_status')
                    ->label('Brevo Sync')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'synced' => 'success',
                        'failed' => 'danger',
                        'skipped' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('welcome_email_status')
                    ->label('Welcome Email')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'opened' => 'success',
                        'delivered', 'sent', 'resent' => 'info',
                        'failed', 'unsubscribed' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('subscribed_at')
                    ->label('Subscribed')
                    ->dateTime('M d, Y h:i A')
                    ->sortable(),
                Tables\Columns\TextColumn::make('welcome_delivered_at')
                    ->label('Delivered')
                    ->dateTime('M d, Y h:i A')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('welcome_opened_at')
                    ->label('Opened')
                    ->dateTime('M d, Y h:i A')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M d, Y h:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('source')
                    ->options(fn (): array => NewsletterSubscriber::query()
                        ->whereNotNull('source')
                        ->distinct()
                        ->orderBy('source')
                        ->pluck('source', 'source')
                        ->all()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('resendWelcome')
                    ->label('Resend Welcome')
                    ->icon('heroicon-o-paper-airplane')
                    ->visible(fn (NewsletterSubscriber $record): bool => in_array($record->welcome_email_status, ['failed', 'sent', 'delivered', 'pending', null], true))
                    ->action(function (NewsletterSubscriber $record): void {
                        $ok = app(BrevoNewsletterService::class)->resendWelcomeEmail($record);

                        Notification::make()
                            ->title($ok ? 'Resend queued in Brevo.' : 'Unable to queue resend.')
                            ->body($ok ? 'Make sure your Brevo resend automation is connected to the resend list.' : ($record->fresh()->last_brevo_error ?: 'Check Brevo resend list configuration.'))
                            ->{$ok ? 'success' : 'danger'}()
                            ->send();
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('subscribed_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNewsletterSubscribers::route('/'),
            'view' => Pages\ViewNewsletterSubscriber::route('/{record}'),
        ];
    }
}
