<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RetentionReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  int  $daysSinceEnded  Days since subscription ended (7, 30, 60, or 80).
     * @param  int  $daysRemaining  Days left until data deletion (83, 60, 30, or 10).
     */
    public function __construct(
        public User $user,
        public int $daysSinceEnded,
        public int $daysRemaining
    ) {}

    public function envelope(): Envelope
    {
        $subject = match ($this->daysSinceEnded) {
            7 => 'Your '.config('app.name').' subscription has ended',
            30 => '60 days left to reactivate your '.config('app.name').' subscription',
            60 => '30 days left to reactivate before your data is deleted',
            80 => '10 days left! Reactivate now to keep your wholesale orders and settings',
            default => 'Reactivate your '.config('app.name').' subscription',
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.retention-reminder',
            with: [
                'userName' => $this->user->name,
                'daysRemaining' => $this->daysRemaining,
                'reactivateUrl' => route('subscription.expired'),
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
