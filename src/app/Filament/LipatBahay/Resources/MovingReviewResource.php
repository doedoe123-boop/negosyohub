<?php

namespace App\Filament\LipatBahay\Resources;

use App\Filament\LipatBahay\Resources\MovingReviewResource\Pages;
use App\Models\MovingReview;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MovingReviewResource extends Resource
{
    protected static ?string $model = MovingReview::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static ?string $navigationGroup = 'Services';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Customer Review';

    protected static ?string $pluralModelLabel = 'Customer Reviews';

    public static function getEloquentQuery(): Builder
    {
        $store = auth()->user()?->getStoreForPanel();

        return parent::getEloquentQuery()
            ->where('store_id', $store?->id)
            ->with(['customer', 'booking']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable(),

                Tables\Columns\TextColumn::make('rating')
                    ->label('Rating')
                    ->formatStateUsing(fn (int $state) => str_repeat('★', $state).str_repeat('☆', 5 - $state))
                    ->sortable(),

                Tables\Columns\TextColumn::make('comment')
                    ->limit(80)
                    ->wrap(),

                Tables\Columns\IconColumn::make('is_published')
                    ->label('Published')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_published')
                    ->label('Published'),
            ])
            ->actions([
                Tables\Actions\Action::make('togglePublished')
                    ->label(fn (MovingReview $record) => $record->is_published ? 'Unpublish' : 'Publish')
                    ->icon(fn (MovingReview $record) => $record->is_published ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                    ->action(fn (MovingReview $record) => $record->update(['is_published' => ! $record->is_published])),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMovingReviews::route('/'),
        ];
    }
}
