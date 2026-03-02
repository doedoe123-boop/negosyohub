<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewOrderReceived extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Order $order,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Order Received — #'.$this->order->reference,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $store = $this->order->store;
        $total = number_format($this->order->total->value / 100, 2);
        $earning = number_format($this->order->store_earning->value / 100, 2);

        return new Content(
            view: 'emails.new-order-received',
            with: [
                'ownerName' => $store?->owner?->name ?? 'Store Owner',
                'storeName' => $store?->name ?? 'Your Store',
                'orderReference' => $this->order->reference,
                'orderId' => $this->order->id,
                'total' => $total,
                'earning' => $earning,
                'currencyCode' => $this->order->currency_code,
                'status' => $this->order->status,
                'dashboardUrl' => $store ? url($store->dashboardPath()) : null,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
