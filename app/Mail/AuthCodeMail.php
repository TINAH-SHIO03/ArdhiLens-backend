<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AuthCodeMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public readonly string $recipientEmail,
        public readonly string $purpose,
        public readonly string $code,
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->purpose === 'password_reset'
            ? 'ArdhiLens password reset code'
            : 'ArdhiLens email verification code';

        return new Envelope(
            subject: $subject,
            from: new Address(
                (string) config('mail.from.address'),
                (string) config('mail.from.name'),
            ),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.auth_code',
            with: [
                'purpose' => $this->purpose,
                'code' => $this->code,
                'isReset' => $this->purpose === 'password_reset',
            ],
        );
    }
}
