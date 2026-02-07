<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StoreInviteMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  array{store_name?: string|null, owner_name?: string|null}  $inviteMetadata
     */
    public function __construct(
        public string $email,
        public string $creatorName,
        public string $inviteToken,
        public array $inviteMetadata,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->creatorName.' invited you to connect on '.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.store-invite',
            with: [
                'creatorName' => $this->creatorName,
                'creatorForward' => true,
                'acceptUrl' => route('invites.accept', ['token' => $this->inviteToken]),
                'metadata' => $this->inviteMetadata,
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
