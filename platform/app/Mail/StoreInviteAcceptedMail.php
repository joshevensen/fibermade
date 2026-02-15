<?php

namespace App\Mail;

use App\Models\Invite;
use App\Models\Store;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StoreInviteAcceptedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invite $invite,
        public Store $store,
    ) {}

    public function envelope(): Envelope
    {
        $storeName = $this->store->name ?? 'A store';

        return new Envelope(
            subject: "{$storeName} accepted your invite on Fibermade",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invite-accepted',
            with: [
                'storesUrl' => route('stores.index'),
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
