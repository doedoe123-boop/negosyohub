<?php

namespace App\Filament\LipatBahay\Resources\MovingBookingResource\Pages;

use App\Filament\LipatBahay\Resources\MovingBookingResource;
use App\MovingBookingStatus;
use App\Services\MovingBookingService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewMovingBooking extends ViewRecord
{
    protected static string $resource = MovingBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('confirm')
                ->label('Confirm')
                ->icon('heroicon-o-check-circle')
                ->color('info')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->status->canTransitionTo(MovingBookingStatus::Confirmed))
                ->action(fn () => $this->transitionBooking(MovingBookingStatus::Confirmed, 'Booking confirmed')),
            Actions\Action::make('start_move')
                ->label('Start Move')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->status->canTransitionTo(MovingBookingStatus::InProgress))
                ->action(fn () => $this->transitionBooking(MovingBookingStatus::InProgress, 'Move started')),
            Actions\Action::make('complete')
                ->label('Complete')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->status->canTransitionTo(MovingBookingStatus::Completed))
                ->action(fn () => $this->transitionBooking(MovingBookingStatus::Completed, 'Booking completed')),
            Actions\Action::make('cancel')
                ->label('Cancel')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->status->canTransitionTo(MovingBookingStatus::Cancelled))
                ->action(fn () => $this->transitionBooking(MovingBookingStatus::Cancelled, 'Booking cancelled')),
        ];
    }

    private function transitionBooking(MovingBookingStatus $target, string $message): void
    {
        $this->record = app(MovingBookingService::class)->updateStatus($this->record, $target);

        Notification::make()
            ->title($message)
            ->success()
            ->send();
    }
}
