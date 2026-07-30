<?php

namespace App\Services;

use App\Models\Plot;
use App\Models\User;
use App\Models\VerificationCertificate;
use App\Models\VerificationLog;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CertificateService
{
    public const TYPE_BUYER = 'buyer_verification';

    public const TYPE_SELLER = 'seller_ownership';

    public function __construct(
        public readonly DigitalSignatureService $digitalSignatureService,
        public readonly QrCodeService $qrCodeService,
    ) {}

    public function resolveTypeForUser(User $user, bool $ownerLinkPassed): string
    {
        if ($user->isSeller()) {
            return self::TYPE_SELLER;
        }

        return self::TYPE_BUYER;
    }

    public function generateCertificateNumber(string $type = self::TYPE_BUYER): string
    {
        $prefix = $type === self::TYPE_SELLER ? 'AL-OWN' : 'AL-BUY';
        $date = now()->format('Ymd');
        $random = strtoupper(Str::random(6));

        return "{$prefix}-{$date}-{$random}";
    }

    public function generateCertificate(
        User $user,
        VerificationLog $verificationLog,
        Plot $plot,
        ?string $certificateType = null,
    ): VerificationCertificate {
        $type = $certificateType ?? $this->resolveTypeForUser(
            $user,
            (bool) data_get($verificationLog->ai_payload, 'verification_context.owner_link_passed')
        );

        $certificateNumber = $this->generateCertificateNumber($type);
        $isSellerCert = $type === self::TYPE_SELLER;

        $certificateData = [
            'certificate_type' => $type,
            'certificate_title' => $isSellerCert
                ? 'Ownership Attestation Certificate'
                : 'Pre-Purchase Verification Certificate',
            'certificate_number' => $certificateNumber,
            'holder_name' => $user->name,
            'holder_email' => $user->email,
            'holder_role' => strtolower((string) ($user->role ?? 'buyer')),
            'plot_reference' => $plot->plot_reference,
            'plot_location' => "{$plot->ward}, {$plot->district}, {$plot->region}",
            'plot_size_hectares' => $plot->size_hectares,
            'land_use' => $plot->land_use,
            'tenure_type' => $plot->tenure_type,
            'verdict' => $verificationLog->ai_verdict,
            'risk_score' => $verificationLog->risk_score,
            'verdict_label' => $isSellerCert
                ? $this->ownershipStatusLabel($verificationLog)
                : $verificationLog->ai_verdict,
            'purpose' => $isSellerCert
                ? 'Confirms registered owner identity and plot linkage for listing.'
                : 'Documents due-diligence checks completed before purchase.',
            'geolocation_passed' => (bool) $verificationLog->geolocation_passed,
            'nida_passed' => (bool) $verificationLog->nida_passed,
            'certificate_passed' => (bool) $verificationLog->certificate_passed,
            'owner_link_passed' => (bool) (
                data_get($verificationLog->ai_payload, 'verification_context.owner_link_passed')
                ?? $verificationLog->certificate_passed
            ),
            'issued_at' => now()->toIso8601String(),
            'issuer' => 'ArdhiLens Land Verification System',
            'signing_algorithm' => 'RSA-SHA256',
        ];

        $signatureData = json_encode($certificateData, JSON_UNESCAPED_SLASHES);
        $signature = $this->digitalSignatureService->sign((string) $signatureData);
        $publicKey = $this->digitalSignatureService->getPublicKey();

        return VerificationCertificate::create([
            'user_id' => $user->id,
            'verification_log_id' => $verificationLog->id,
            'certificate_number' => $certificateNumber,
            'certificate_type' => $type,
            'certificate_data' => $certificateData,
            'signature' => $signature,
            'public_key' => $publicKey,
            'issued_at' => now(),
            'expires_at' => now()->addYears(1),
        ]);
    }

    public function generatePdf(VerificationCertificate $certificate): string
    {
        $verifyUrl = $this->buildQrData($certificate);
        $qrDataUri = $this->qrCodeService->toDataUri($verifyUrl, 200);

        $type = $certificate->certificate_type
            ?? ($certificate->certificate_data['certificate_type'] ?? self::TYPE_BUYER);

        $view = $type === self::TYPE_SELLER
            ? 'certificates.ownership_attestation'
            : 'certificates.verification';

        $data = [
            'certificate' => $certificate,
            'data' => $certificate->certificate_data,
            'signature' => $certificate->signature,
            'fingerprint' => $this->digitalSignatureService->getPublicKeyFingerprint(),
            'qr_data' => $verifyUrl,
            'qr_image' => $qrDataUri,
            'signing_algorithm' => 'RSA-2048 / SHA-256',
        ];

        $pdf = app('dompdf.wrapper')->loadView($view, $data);
        $pdf->setPaper('a4', 'portrait');
        $pdf->setOption('isRemoteEnabled', true);

        $binary = $pdf->output();
        $pdfHash = hash('sha256', $binary);
        $pdfSignature = $this->digitalSignatureService->sign($pdfHash);

        $fileName = "certificate_{$certificate->certificate_number}.pdf";
        $filePath = "certificates/{$fileName}";

        Storage::disk('local')->put($filePath, $binary);

        $certificate->update([
            'pdf_path' => $filePath,
            'pdf_content_hash' => $pdfHash,
            'pdf_signature' => $pdfSignature,
        ]);

        $this->sendCertificateEmail($certificate);

        return $filePath;
    }

    public function sendCertificateEmail(VerificationCertificate $certificate, bool $force = false): bool
    {
        $data = $certificate->certificate_data ?? [];
        if (! $force && ! empty($data['email_sent_at'])) {
            return false;
        }

        if (! $certificate->pdf_path) {
            return false;
        }

        $user = $certificate->loadMissing('user')->user;
        if (! $user || empty($user->email)) {
            return false;
        }

        try {
            \Illuminate\Support\Facades\Mail::mailer('smtp')->to($user->email)->send(
                new \App\Mail\CertificateIssuedMail($user, $certificate, $certificate->pdf_path)
            );

            $certificate->update([
                'certificate_data' => array_merge($data, [
                    'email_sent_at' => now()->toIso8601String(),
                ]),
            ]);

            return true;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Certificate email delivery failed', [
                'certificate_id' => $certificate->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function verifyCertificate(string $certificateNumber): ?array
    {
        $certificate = VerificationCertificate::where('certificate_number', $certificateNumber)->first();

        if (! $certificate) {
            return null;
        }

        $signatureData = json_encode($certificate->certificate_data, JSON_UNESCAPED_SLASHES);
        $isValid = $this->digitalSignatureService->verify((string) $signatureData, $certificate->signature);

        $pdfValid = null;
        if ($certificate->pdf_content_hash && $certificate->pdf_signature) {
            $pdfValid = $this->digitalSignatureService->verify(
                $certificate->pdf_content_hash,
                $certificate->pdf_signature
            );
        }

        return [
            'found' => true,
            'valid_signature' => $isValid,
            'valid_pdf_signature' => $pdfValid,
            'is_expired' => ! $certificate->isValid(),
            'certificate' => $certificate,
        ];
    }

    private function ownershipStatusLabel(VerificationLog $log): string
    {
        $ownerLink = (bool) data_get($log->ai_payload, 'verification_context.owner_link_passed');

        if ($ownerLink && $log->nida_passed && $log->geolocation_passed) {
            return 'OWNERSHIP VERIFIED';
        }

        if ($log->nida_passed) {
            return 'IDENTITY VERIFIED';
        }

        return 'ATTESTATION RECORDED';
    }

    private function buildQrData(VerificationCertificate $certificate): string
    {
        $domain = rtrim((string) config('certificates.verification_domain', url('/verify')), '/');

        return "{$domain}/{$certificate->certificate_number}";
    }
}
