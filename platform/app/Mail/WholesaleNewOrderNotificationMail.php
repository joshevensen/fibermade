<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WholesaleNewOrderNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        $storeName = $this->order->orderable?->name ?? 'Store';

        return new Envelope(
            subject: "New Wholesale Order from {$storeName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.wholesale.new-order-notification',
            with: [
                'orderUrl' => route('orders.edit', $this->order),
            ],
        );
    }

    /**
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
