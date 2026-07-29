<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProcedureAlertMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly string $alertTitle,
        public readonly string $alertBody,
        public readonly string $procedureGuide,
        public readonly array $payload = [],
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[ArdhiLens] '.$this->alertTitle,
            from: new Address(
                (string) config('mail.from.address'),
                (string) config('mail.from.name'),
            ),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.procedure_alert',
            with: [
                'userName' => $this->user->name,
                'role' => strtolower((string) $this->user->role),
                'alertTitle' => $this->alertTitle,
                'alertBody' => $this->alertBody,
                'procedureGuide' => $this->procedureGuide,
                'payload' => $this->payload,
            ],
        );
    }
}
