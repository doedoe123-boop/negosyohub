<?php

namespace App\Filament\Realty\Resources;

use App\Filament\Realty\Resources\TestimonialResource\Pages;
use App\Models\Property;
use App\Models\Testimonial;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TestimonialResource extends Resource
{
    protected static ?string $model = Testimonial::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-bottom-center-text';

    protected static ?string $navigationGroup = 'Engagement';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'client_name';

    public static function getEloquentQuery(): Builder
    {
        $store = auth()->user()?->getStoreForPanel();

        return parent::getEloquentQuery()
            ->where('store_id', $store?->id)
            ->with('property');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Client Information')
                    ->schema([
                        Forms\Components\TextInput::make('client_name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('client_email')
                            ->email()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('client_photo')
                            ->label('Client Photo URL')
                            ->url()
                            ->maxLength(500),
                    ])->columns(3),

                Forms\Components\Section::make('Review')
                    ->schema([
                        Forms\Components\Select::make('rating')
                            ->options([
                                5 => '★★★★★ (5)',
                                4 => '★★★★☆ (4)',
                                3 => '★★★☆☆ (3)',
                                2 => '★★☆☆☆ (2)',
                                1 => '★☆☆☆☆ (1)',
                            ])
                            ->required()
                            ->default(5)
                            ->native(false),

                        Forms\Components\Select::make('property_id')
                            ->label('Related Property (optional)')
                            ->options(function (): array {
                                $store = auth()->user()?->getStoreForPanel();

                                return Property::query()
                                    ->where('store_id', $store?->id)
                                    ->orderBy('title')
                                    ->pluck('title', 'id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->placeholder('General store review'),

                        Forms\Components\TextInput::make('title')
                            ->label('Review Title')
                            ->nullable()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('content')
                            ->required()
                            ->maxLength(2000)
                            ->rows(4)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Publishing')
                    ->schema([
                        Forms\Components\Toggle::make('is_featured')
                            ->label('Featured Testimonial'),

                        Forms\Components\Toggle::make('is_published')
                            ->label('Published')
                            ->default(false),

                        Forms\Components\DateTimePicker::make('published_at')
                            ->label('Publish Date'),
                    ])->columns(3),

                Forms\Components\Section::make('Agent Reply')
                    ->description('Your public response to this testimonial, visible to prospective clients.')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Textarea::make('agent_reply')
                            ->label('Reply')
                            ->nullable()
                            ->maxLength(1000)
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\DateTimePicker::make('replied_at')
                            ->label('Replied At')
                            ->disabled()
                            ->dehydrated(false),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('client_name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('rating')
                    ->formatStateUsing(fn (int $state): string => str_repeat('★', $state).str_repeat('☆', 5 - $state))
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->limit(30)
                    ->placeholder('None')
                    ->searchable(),

                Tables\Columns\TextColumn::make('content')
                    ->limit(50)
                    ->tooltip(fn (Model $record): string => $record->content)
                    ->searchable(),

                Tables\Columns\TextColumn::make('property.title')
                    ->label('Property')
                    ->limit(25)
                    ->placeholder('General')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_featured')
                    ->boolean()
                    ->label('Featured')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_published')
                    ->boolean()
                    ->label('Published')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M d, Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('rating')
                    ->options([
                        5 => '5 Stars',
                        4 => '4 Stars',
                        3 => '3 Stars',
                        2 => '2 Stars',
                        1 => '1 Star',
                    ]),

                Tables\Filters\TernaryFilter::make('is_published')
                    ->label('Published'),

                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('Featured'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('reply')
                    ->label('Reply')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('info')
                    ->modalHeading('Reply to Testimonial')
                    ->modalDescription(fn (Model $record): string => "\"{$record->content}\" — {$record->client_name}")
                    ->visible(fn (Model $record): bool => blank($record->agent_reply))
                    ->form([
                        Forms\Components\Textarea::make('agent_reply')
                            ->label('Your Reply')
                            ->required()
                            ->maxLength(1000)
                            ->rows(4),
                    ])
                    ->action(function (Model $record, array $data): void {
                        $record->update([
                            'agent_reply' => $data['agent_reply'],
                            'replied_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Reply posted successfully')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('edit_reply')
                    ->label('Edit Reply')
                    ->icon('heroicon-o-pencil-square')
                    ->color('gray')
                    ->modalHeading('Edit Your Reply')
                    ->visible(fn (Model $record): bool => filled($record->agent_reply))
                    ->fillForm(fn (Model $record): array => [
                        'agent_reply' => $record->agent_reply,
                    ])
                    ->form([
                        Forms\Components\Textarea::make('agent_reply')
                            ->label('Your Reply')
                            ->required()
                            ->maxLength(1000)
                            ->rows(4),
                    ])
                    ->action(function (Model $record, array $data): void {
                        $record->update([
                            'agent_reply' => $data['agent_reply'],
                            'replied_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Reply updated successfully')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('publish')
                    ->icon('heroicon-o-globe-alt')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Model $record): bool => ! $record->is_published)
                    ->action(fn (Model $record) => $record->publish()),
                Tables\Actions\Action::make('unpublish')
                    ->icon('heroicon-o-eye-slash')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (Model $record): bool => $record->is_published)
                    ->action(fn (Model $record) => $record->unpublish()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTestimonials::route('/'),
            'create' => Pages\CreateTestimonial::route('/create'),
            'edit' => Pages\EditTestimonial::route('/{record}/edit'),
        ];
    }
}
