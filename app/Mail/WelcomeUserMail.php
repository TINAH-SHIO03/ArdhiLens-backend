<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeUserMail extends Mailable
{
    use SerializesModels;

    public function __construct(public readonly User $user) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Karibu · Welcome to ArdhiLens',
            from: new Address(
                (string) config('mail.from.address'),
                (string) config('mail.from.name'),
            ),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.welcome_user',
            with: [
                'userName' => $this->user->name,
                'role' => strtolower((string) $this->user->role),
                'email' => $this->user->email,
            ],
        );
    }
}
