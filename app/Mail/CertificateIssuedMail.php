<?php

namespace App\Mail;

use App\Models\User;
use App\Models\VerificationCertificate;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CertificateIssuedMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly VerificationCertificate $certificate,
        public readonly string $pdfStoragePath,
    ) {}

    public function envelope(): Envelope
    {
        $title = $this->certificate->certificate_data['certificate_title']
            ?? 'ArdhiLens Verification Certificate';

        return new Envelope(
            subject: "Your certificate is ready — {$title}",
            from: new Address(
                (string) config('mail.from.address'),
                (string) config('mail.from.name'),
            ),
        );
    }

    public function content(): Content
    {
        $data = $this->certificate->certificate_data ?? [];

        return new Content(
            markdown: 'emails.certificate_issued',
            with: [
                'userName' => $this->user->name,
                'certificateNumber' => $this->certificate->certificate_number,
                'certificateTitle' => $data['certificate_title'] ?? 'Verification Certificate',
                'plotReference' => $data['plot_reference'] ?? 'N/A',
                'verdict' => $data['verdict'] ?? 'N/A',
                'issuedAt' => $this->certificate->issued_at?->format('d M Y H:i'),
            ],
        );
    }

    public function attachments(): array
    {
        $fileName = 'ArdhiLens_'.$this->certificate->certificate_number.'.pdf';

        return [
            Attachment::fromStorageDisk('local', $this->pdfStoragePath)
                ->as($fileName)
                ->withMime('application/pdf'),
        ];
    }
}
