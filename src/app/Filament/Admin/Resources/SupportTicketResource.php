<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\SupportTicketResource\Pages;
use App\Models\SupportTicket;
use App\Models\User;
use App\TicketCategory;
use App\TicketPriority;
use App\TicketStatus;
use App\UserRole;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SupportTicketResource extends Resource
{
    protected static ?string $model = SupportTicket::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationGroup = 'Support';

    protected static ?string $navigationLabel = 'Tickets';

    protected static ?string $modelLabel = 'Ticket';

    protected static ?string $pluralModelLabel = 'Tickets';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return (string) SupportTicket::query()
            ->where('status', TicketStatus::Open)
            ->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Ticket Details')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Submitted By')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(fn (?SupportTicket $record): bool => $record !== null),
                        Forms\Components\Select::make('sector')
                            ->options([
                                'ecommerce' => 'E-Commerce',
                                'real_estate' => 'Real Estate',
                                'paupahan' => 'Paupahan (Rentals)',
                                'lipat_bahay' => 'Lipat Bahay (Movers)',
                            ])
                            ->live()
                            ->placeholder('Global / General'),
                        Forms\Components\Select::make('store_id')
                            ->label('Related Store')
                            ->relationship('store', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('None'),
                        Forms\Components\TextInput::make('subject')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('message')
                            ->required()
                            ->maxLength(5000)
                            ->rows(5)
                            ->columnSpanFull(),
                    ])->columns(2),
                Forms\Components\Section::make('Classification')
                    ->schema([
                        Forms\Components\Select::make('category')
                            ->options(fn (Forms\Get $get) => collect(TicketCategory::forSector($get('sector')))->mapWithKeys(
                                fn (TicketCategory $cat) => [$cat->value => $cat->label()]
                            ))
                            ->required(),
                        Forms\Components\Select::make('priority')
                            ->options(collect(TicketPriority::cases())->mapWithKeys(
                                fn (TicketPriority $p) => [$p->value => ucfirst($p->value)]
                            ))
                            ->required()
                            ->default('medium'),
                        Forms\Components\Select::make('status')
                            ->options(collect(TicketStatus::cases())->mapWithKeys(
                                fn (TicketStatus $s) => [$s->value => str_replace('_', ' ', ucfirst($s->value))]
                            ))
                            ->required()
                            ->default('open'),
                        Forms\Components\Select::make('assigned_to')
                            ->label('Assign To')
                            ->options(fn () => User::query()
                                ->where('role', UserRole::Admin)
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->placeholder('Unassigned'),
                    ])->columns(2),
                Forms\Components\Section::make('Admin Notes')
                    ->schema([
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('')
                            ->placeholder('Internal notes visible only to admin staff...')
                            ->maxLength(5000)
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Ticket Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('subject')
                            ->columnSpanFull()
                            ->weight('bold')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large),
                        Infolists\Components\TextEntry::make('message')
                            ->columnSpanFull()
                            ->prose(),
                    ]),
                Infolists\Components\Section::make('Classification')
                    ->schema([
                        Infolists\Components\TextEntry::make('category')
                            ->badge()
                            ->formatStateUsing(fn (TicketCategory $state): string => $state->label())
                            ->color('gray'),
                        Infolists\Components\TextEntry::make('priority')
                            ->badge()
                            ->formatStateUsing(fn (TicketPriority $state): string => ucfirst($state->value))
                            ->color(fn (TicketPriority $state): string => match ($state) {
                                TicketPriority::Low => 'gray',
                                TicketPriority::Medium => 'info',
                                TicketPriority::High => 'warning',
                                TicketPriority::Urgent => 'danger',
                            }),
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->formatStateUsing(fn (TicketStatus $state): string => str_replace('_', ' ', ucfirst($state->value)))
                            ->color(fn (TicketStatus $state): string => match ($state) {
                                TicketStatus::Open => 'warning',
                                TicketStatus::InProgress => 'info',
                                TicketStatus::Resolved => 'success',
                                TicketStatus::Closed => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('assignee.name')
                            ->label('Assigned To')
                            ->default('Unassigned'),
                    ])->columns(4),
                Infolists\Components\Section::make('Submitted By')
                    ->schema([
                        Infolists\Components\TextEntry::make('user.name')
                            ->label('Name'),
                        Infolists\Components\TextEntry::make('user.email')
                            ->label('Email'),
                        Infolists\Components\TextEntry::make('store.name')
                            ->label('Related Store')
                            ->default('—'),
                        Infolists\Components\TextEntry::make('sector')
                            ->label('Sector')
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'ecommerce' => 'E-Commerce',
                                'real_estate' => 'Real Estate',
                                'paupahan' => 'Paupahan (Rentals)',
                                'lipat_bahay' => 'Lipat Bahay (Movers)',
                                default => 'Global / General',
                            })
                            ->badge()
                            ->color('info'),
                    ])->columns(4),
                Infolists\Components\Section::make('Admin Notes')
                    ->schema([
                        Infolists\Components\TextEntry::make('admin_notes')
                            ->label('')
                            ->default('No notes yet.')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
                Infolists\Components\Section::make('Timestamps')
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('updated_at')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('resolved_at')
                            ->dateTime()
                            ->placeholder('—'),
                    ])->columns(3)
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                Tables\Columns\TextColumn::make('subject')
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Submitted By')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sector')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'ecommerce' => 'E-Commerce',
                        'real_estate' => 'Real Estate',
                        'paupahan' => 'Paupahan (Rentals)',
                        'lipat_bahay' => 'Lipat Bahay (Movers)',
                        default => '—',
                    })
                    ->color('info')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->formatStateUsing(fn (TicketCategory $state): string => $state->label())
                    ->color('gray')
                    ->sortable(),
                Tables\Columns\TextColumn::make('priority')
                    ->badge()
                    ->formatStateUsing(fn (TicketPriority $state): string => ucfirst($state->value))
                    ->color(fn (TicketPriority $state): string => match ($state) {
                        TicketPriority::Low => 'gray',
                        TicketPriority::Medium => 'info',
                        TicketPriority::High => 'warning',
                        TicketPriority::Urgent => 'danger',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (TicketStatus $state): string => str_replace('_', ' ', ucfirst($state->value)))
                    ->color(fn (TicketStatus $state): string => match ($state) {
                        TicketStatus::Open => 'warning',
                        TicketStatus::InProgress => 'info',
                        TicketStatus::Resolved => 'success',
                        TicketStatus::Closed => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('assignee.name')
                    ->label('Assigned To')
                    ->placeholder('Unassigned')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Opened')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(TicketStatus::cases())->mapWithKeys(
                        fn (TicketStatus $s) => [$s->value => str_replace('_', ' ', ucfirst($s->value))]
                    )),
                Tables\Filters\SelectFilter::make('priority')
                    ->options(collect(TicketPriority::cases())->mapWithKeys(
                        fn (TicketPriority $p) => [$p->value => ucfirst($p->value)]
                    )),
                Tables\Filters\SelectFilter::make('category')
                    ->options(collect(TicketCategory::cases())->mapWithKeys(
                        fn (TicketCategory $c) => [$c->value => $c->label()]
                    )),
                Tables\Filters\SelectFilter::make('sector')
                    ->options([
                        'ecommerce' => 'E-Commerce',
                        'real_estate' => 'Real Estate',
                        'paupahan' => 'Paupahan (Rentals)',
                        'lipat_bahay' => 'Lipat Bahay (Movers)',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('resolve')
                    ->label('Resolve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (SupportTicket $record): bool => ! $record->isResolved() && $record->status !== TicketStatus::Closed)
                    ->form([
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Resolution Notes')
                            ->placeholder('Describe how the issue was resolved...')
                            ->maxLength(5000),
                    ])
                    ->action(function (SupportTicket $record, array $data): void {
                        $record->update([
                            'status' => TicketStatus::Resolved,
                            'resolved_at' => now(),
                            'admin_notes' => $data['admin_notes'] ?? $record->admin_notes,
                        ]);

                        Notification::make()
                            ->title('Ticket Resolved')
                            ->body("Ticket #{$record->id} has been marked as resolved.")
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('close')
                    ->label('Close')
                    ->icon('heroicon-o-x-mark')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn (SupportTicket $record): bool => $record->status !== TicketStatus::Closed)
                    ->action(function (SupportTicket $record): void {
                        $record->update([
                            'status' => TicketStatus::Closed,
                            'resolved_at' => $record->resolved_at ?? now(),
                        ]);

                        Notification::make()
                            ->title('Ticket Closed')
                            ->body("Ticket #{$record->id} has been closed.")
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
            'index' => Pages\ListSupportTickets::route('/'),
            'create' => Pages\CreateSupportTicket::route('/create'),
            'view' => Pages\ViewSupportTicket::route('/{record}'),
            'edit' => Pages\EditSupportTicket::route('/{record}/edit'),
        ];
    }
}
