<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PersonnelInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $recipientName,
        public string $recipientEmail,
        public string $temporaryPassword,
        public string $roleName,
        public string $portalUrl,
        public ?string $parkName = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'You have been invited to the WildWatch UWA Portal',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.personnel-invite',
        );
    }
}
