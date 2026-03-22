<?php

namespace App\Notifications;

use App\Models\Order;
use App\OrderStatus;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifies the customer when their order status changes.
 *
 * Sent on every status transition after the initial placement:
 * Confirmed → Preparing → Shipped → Delivered → Cancelled
 *
 * Channels: mail + database (in-app bell for Phase 5 customer dashboard)
 */
class OrderStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Order $order,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $status = OrderStatus::from($this->order->status);
        $storeName = $this->order->store?->name ?? config('app.name');

        return (new MailMessage)
            ->subject("Order #{$this->order->reference} — {$status->label()}")
            ->greeting("Hi {$notifiable->name}!")
            ->line($this->statusMessage($status, $storeName))
            ->line("**Order Reference:** #{$this->order->reference}")
            ->when(
                $status === OrderStatus::Confirmed,
                fn (MailMessage $m) => $m->line('Sit tight — your order is being prepared soon.')
            )
            ->when(
                $status === OrderStatus::Shipped,
                fn (MailMessage $m) => $m->line('Your package is now on the way.')
            )
            ->when(
                $status === OrderStatus::Cancelled,
                fn (MailMessage $m) => $m->line('If you have questions, please contact the store or our support team.')
            )
            ->salutation("— {$storeName}");
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $status = OrderStatus::from($this->order->status);

        return FilamentNotification::make()
            ->title("Order #{$this->order->reference} — {$status->label()}")
            ->body($this->statusMessage($status, $this->order->store?->name ?? config('app.name')))
            ->icon($this->statusIcon($status))
            ->iconColor($status->color())
            ->getDatabaseMessage();
    }

    private function statusMessage(OrderStatus $status, string $storeName): string
    {
        return match ($status) {
            OrderStatus::Confirmed => "Your order has been confirmed by {$storeName} and will be prepared shortly.",
            OrderStatus::Preparing => "{$storeName} is now preparing your order.",
            OrderStatus::Shipped => "Your order from {$storeName} is now on the way.",
            OrderStatus::Delivered => 'Your order has been delivered. Enjoy!',
            OrderStatus::Cancelled => "Your order has been cancelled by {$storeName}.",
            default => "Your order status has been updated to: {$status->label()}.",
        };
    }

    private function statusIcon(OrderStatus $status): string
    {
        return match ($status) {
            OrderStatus::Confirmed => 'heroicon-o-check-circle',
            OrderStatus::Preparing => 'heroicon-o-fire',
            OrderStatus::Shipped => 'heroicon-o-truck',
            OrderStatus::Delivered => 'heroicon-o-truck',
            OrderStatus::Cancelled => 'heroicon-o-x-circle',
            default => 'heroicon-o-information-circle',
        };
    }
}
