<?php

namespace App\Services;

use App\Models\User;
use App\Models\VerificationCertificate;
use App\Models\VerificationLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AdminCertificateAssistService
{
    public function __construct(
        private readonly CertificateService $certificates,
    ) {}

    /**
     * @return array{eligible: bool, reason: ?string, mode: string}
     */
    public function eligibilityForLog(VerificationLog $log): array
    {
        $log->loadMissing(['user', 'plot', 'certificate']);

        if ($log->certificate) {
            return [
                'eligible' => false,
                'reason' => 'Certificate already issued ('.$log->certificate->certificate_number.').',
                'mode' => $this->resolveMode($log),
            ];
        }

        if ($log->status !== 'Completed') {
            return [
                'eligible' => false,
                'reason' => 'Verification is not completed (status: '.$log->status.').',
                'mode' => $this->resolveMode($log),
            ];
        }

        if (! $log->nida_passed) {
            return [
                'eligible' => false,
                'reason' => 'NIDA identity step did not pass.',
                'mode' => $this->resolveMode($log),
            ];
        }

        if (! in_array($log->ai_verdict, ['SAFE', 'CAUTION'], true)) {
            return [
                'eligible' => false,
                'reason' => 'Verdict must be SAFE or CAUTION (current: '.($log->ai_verdict ?? 'unknown').').',
                'mode' => $this->resolveMode($log),
            ];
        }

        $mode = $this->resolveMode($log);
        $isSellerMode = $mode === CertificateService::TYPE_SELLER;

        if ($isSellerMode) {
            $ownerLinkPassed = (bool) data_get($log->ai_payload, 'verification_context.owner_link_passed');

            if (! $ownerLinkPassed) {
                return [
                    'eligible' => false,
                    'reason' => 'Seller ownership link did not pass (NIN must match plot owner).',
                    'mode' => $mode,
                ];
            }
        }

        if (! $log->plot) {
            return [
                'eligible' => false,
                'reason' => 'Plot record is missing for this verification log.',
                'mode' => $mode,
            ];
        }

        if (! $log->user) {
            return [
                'eligible' => false,
                'reason' => 'User record is missing for this verification log.',
                'mode' => $mode,
            ];
        }

        return [
            'eligible' => true,
            'reason' => null,
            'mode' => $mode,
        ];
    }

    public function issueForLog(VerificationLog $log, ?User $admin = null): VerificationCertificate
    {
        $check = $this->eligibilityForLog($log);

        if (! $check['eligible']) {
            throw new \InvalidArgumentException($check['reason'] ?? 'Not eligible for certificate issuance.');
        }

        $log->loadMissing(['user', 'plot']);

        $certificate = $this->certificates->generateCertificate(
            $log->user,
            $log,
            $log->plot,
            $check['mode'],
        );

        if (! $this->pdfExists($certificate)) {
            $this->certificates->generatePdf($certificate);
            $certificate->refresh();
        }

        if ($admin) {
            Log::info('Admin issued verification certificate', [
                'admin_id' => $admin->id,
                'verification_log_id' => $log->id,
                'certificate_id' => $certificate->id,
                'certificate_number' => $certificate->certificate_number,
            ]);
        }

        return $certificate;
    }

    public function regeneratePdf(VerificationCertificate $certificate, ?User $admin = null): VerificationCertificate
    {
        $this->certificates->generatePdf($certificate);
        $certificate->refresh();

        if ($admin) {
            Log::info('Admin regenerated certificate PDF', [
                'admin_id' => $admin->id,
                'certificate_id' => $certificate->id,
                'certificate_number' => $certificate->certificate_number,
            ]);
        }

        return $certificate;
    }

    public function resendEmail(VerificationCertificate $certificate, ?User $admin = null): bool
    {
        $sent = $this->certificates->sendCertificateEmail($certificate, force: true);

        if ($admin) {
            Log::info('Admin resent certificate email', [
                'admin_id' => $admin->id,
                'certificate_id' => $certificate->id,
                'sent' => $sent,
            ]);
        }

        return $sent;
    }

    public function verifyUrl(VerificationCertificate $certificate): string
    {
        $domain = rtrim((string) config('certificates.verification_domain', url('/verify')), '/');

        return "{$domain}/{$certificate->certificate_number}";
    }

    public function pdfExists(VerificationCertificate $certificate): bool
    {
        return filled($certificate->pdf_path)
            && Storage::disk('local')->exists($certificate->pdf_path);
    }

    public function typeLabel(?string $type): string
    {
        return match ($type) {
            CertificateService::TYPE_SELLER => 'Ownership Attestation',
            CertificateService::TYPE_BUYER => 'Pre-Purchase',
            default => $type ?? 'Unknown',
        };
    }

    private function resolveMode(VerificationLog $log): string
    {
        if ($log->user?->isSeller()) {
            return CertificateService::TYPE_SELLER;
        }

        return CertificateService::TYPE_BUYER;
    }
}
