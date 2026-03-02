<?php

namespace App\Filament\Pages;

use App\Models\SupportTicket;
use App\TicketCategory;
use App\TicketPriority;
use App\TicketStatus;
use Filament\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StoreSupportTickets extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationGroup = 'Support';

    protected static ?int $navigationSort = 10;

    protected static ?string $title = 'Support Tickets';

    protected static string $view = 'filament.pages.store-support-tickets';

    public function table(Table $table): Table
    {
        $user = auth()->user();
        $store = $user?->getStoreForPanel();

        return $table
            ->query(
                SupportTicket::query()
                    ->where('user_id', $user?->id)
                    ->when($store, fn (Builder $q) => $q->where('store_id', $store->id))
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('subject')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(fn (SupportTicket $record): string => $record->subject),

                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->formatStateUsing(fn (TicketCategory $state): string => $state->label())
                    ->color('primary'),

                Tables\Columns\TextColumn::make('priority')
                    ->badge()
                    ->formatStateUsing(fn (TicketPriority $state): string => $state->name)
                    ->color(fn (TicketPriority $state): string => match ($state) {
                        TicketPriority::Urgent => 'danger',
                        TicketPriority::High => 'warning',
                        TicketPriority::Medium => 'info',
                        TicketPriority::Low => 'gray',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (TicketStatus $state): string => $state->name)
                    ->color(fn (TicketStatus $state): string => match ($state) {
                        TicketStatus::Open => 'info',
                        TicketStatus::InProgress => 'warning',
                        TicketStatus::Resolved => 'success',
                        TicketStatus::Closed => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('resolved_at')
                    ->label('Resolved')
                    ->dateTime('M d, Y H:i')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(TicketStatus::cases())->mapWithKeys(
                        fn (TicketStatus $s) => [$s->value => $s->name]
                    )),
                Tables\Filters\SelectFilter::make('category')
                    ->options(collect(TicketCategory::cases())->mapWithKeys(
                        fn (TicketCategory $c) => [$c->value => $c->label()]
                    )),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading('Ticket Details')
                    ->form([
                        Grid::make(2)->schema([
                            TextInput::make('subject')
                                ->disabled()
                                ->columnSpanFull(),
                            Select::make('category')
                                ->options(collect(TicketCategory::cases())->mapWithKeys(
                                    fn (TicketCategory $c) => [$c->value => $c->label()]
                                ))
                                ->disabled(),
                            Select::make('priority')
                                ->options(collect(TicketPriority::cases())->mapWithKeys(
                                    fn (TicketPriority $p) => [$p->value => $p->name]
                                ))
                                ->disabled(),
                            Select::make('status')
                                ->options(collect(TicketStatus::cases())->mapWithKeys(
                                    fn (TicketStatus $s) => [$s->value => $s->name]
                                ))
                                ->disabled()
                                ->columnSpanFull(),
                            Textarea::make('message')
                                ->disabled()
                                ->columnSpanFull(),
                            Textarea::make('admin_notes')
                                ->label('Admin Response')
                                ->disabled()
                                ->columnSpanFull()
                                ->placeholder('No response yet.'),
                        ]),
                    ]),
            ])
            ->emptyStateHeading('No support tickets yet')
            ->emptyStateDescription('Submit a ticket if you need help from our admin team.')
            ->emptyStateIcon('heroicon-o-chat-bubble-left-right');
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('new_ticket')
                ->label('New Ticket')
                ->icon('heroicon-o-plus-circle')
                ->color('primary')
                ->form([
                    Grid::make(2)->schema([
                        TextInput::make('subject')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Select::make('category')
                            ->options(collect(TicketCategory::cases())->mapWithKeys(
                                fn (TicketCategory $c) => [$c->value => $c->label()]
                            ))
                            ->required()
                            ->native(false),

                        Select::make('priority')
                            ->options(collect(TicketPriority::cases())->mapWithKeys(
                                fn (TicketPriority $p) => [$p->value => $p->name]
                            ))
                            ->default(TicketPriority::Medium->value)
                            ->required()
                            ->native(false),

                        Textarea::make('message')
                            ->required()
                            ->rows(5)
                            ->maxLength(5000)
                            ->columnSpanFull(),
                    ]),
                ])
                ->action(function (array $data): void {
                    $user = auth()->user();
                    $store = $user?->getStoreForPanel();

                    SupportTicket::create([
                        'user_id' => $user->id,
                        'store_id' => $store?->id,
                        'subject' => $data['subject'],
                        'category' => $data['category'],
                        'priority' => $data['priority'],
                        'message' => $data['message'],
                        'status' => TicketStatus::Open->value,
                    ]);

                    Notification::make()
                        ->success()
                        ->title('Ticket submitted')
                        ->body('Our admin team will review your ticket shortly.')
                        ->send();
                }),
        ];
    }
}
