<?php

namespace App\Mail;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WholesaleOrderStatusUpdateMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        $statusLabel = ucfirst($this->order->status->value);

        return new Envelope(
            subject: "Order #{$this->order->id} — {$statusLabel}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.wholesale.order-status-update',
            with: [
                'statusMessage' => $this->statusMessage(),
                'statusLabel' => ucfirst($this->order->status->value),
            ],
        );
    }

    public function statusMessage(): string
    {
        return match ($this->order->status) {
            OrderStatus::Accepted => 'Your order has been accepted and is being prepared.',
            OrderStatus::Fulfilled => 'Your order has been fulfilled and is ready for shipment.',
            OrderStatus::Delivered => 'Your order has been marked as delivered.',
            OrderStatus::Cancelled => 'Your order has been cancelled.',
            default => '',
        };
    }

    /**
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
