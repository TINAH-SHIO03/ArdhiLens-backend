<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VerificationCertificate;
use App\Models\VerificationLog;
use App\Services\CertificateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CertificateController extends Controller
{
    public function __construct(
        public readonly CertificateService $certificateService,
    ) {}

    public function generate(Request $request): JsonResponse
    {
        $validator = \Validator::make($request->all(), [
            'verification_log_id' => ['required', 'integer'],
        ]);

        if ($validator->fails()) {
            return $this->error(__('api.certificates.validation_failed'), [
                'validation' => $validator->errors()->toArray(),
            ], 422);
        }

        $user = $request->user();

        if (! $user) {
            return $this->error(__('api.certificates.unauthenticated'), [], 401);
        }

        $verificationLog = VerificationLog::query()
            ->where('id', (int) $request->input('verification_log_id'))
            ->where('user_id', (int) $user->id)
            ->first();

        if (! $verificationLog) {
            return $this->error(__('api.certificates.verification_log_not_found'), [], 404);
        }

        if ($verificationLog->status !== 'Completed') {
            return $this->error(__('api.certificates.verification_not_completed'), [], 422);
        }

        if (! in_array($verificationLog->ai_verdict, ['SAFE', 'CAUTION'], true)) {
            return $this->error(__('api.certificates.certificate_not_eligible'), [], 422);
        }

        if (! $verificationLog->nida_passed) {
            return $this->error(__('api.certificates.certificate_not_eligible'), [], 422);
        }

        $existing = VerificationCertificate::where('verification_log_id', $verificationLog->id)->first();

        if ($existing) {
            if (! $existing->pdf_path || ! Storage::disk('local')->exists($existing->pdf_path)) {
                $this->certificateService->generatePdf($existing);
                $existing->refresh();
            }

            return $this->success(__('api.certificates.certificate_already_exists'), [
                'certificate' => $this->certificatePayload($existing),
            ]);
        }

        $plot = $verificationLog->plot;

        if (! $plot) {
            return $this->error(__('api.certificates.plot_not_found'), [], 404);
        }

        $certificate = $this->certificateService->generateCertificate($user, $verificationLog, $plot);
        $this->certificateService->generatePdf($certificate);
        $certificate->refresh();

        return $this->success(__('api.certificates.certificate_generated'), [
            'certificate' => $this->certificatePayload($certificate),
        ], 201);
    }

    public function download(Request $request, int $id): JsonResponse|\Symfony\Component\HttpFoundation\Response
    {
        $user = $request->user();

        if (! $user) {
            return $this->error(__('api.certificates.unauthenticated'), [], 401);
        }

        $certificate = VerificationCertificate::query()
            ->where('id', $id)
            ->where('user_id', (int) $user->id)
            ->first();

        if (! $certificate) {
            return $this->error(__('api.certificates.certificate_not_found'), [], 404);
        }

        if (! $certificate->pdf_path || ! Storage::disk('local')->exists($certificate->pdf_path)) {
            try {
                $this->certificateService->generatePdf($certificate);
                $certificate->refresh();
            } catch (\Throwable $e) {
                \Log::error('Certificate PDF download generation failed', [
                    'certificate_id' => $certificate->id,
                    'error' => $e->getMessage(),
                ]);

                return $this->error(__('api.certificates.pdf_generation_failed'), [], 500);
            }
        }

        $filePath = $certificate->pdf_path;

        if (! $filePath || ! Storage::disk('local')->exists($filePath)) {
            return $this->error(__('api.certificates.pdf_generation_failed'), [], 500);
        }

        return Storage::disk('local')->download(
            $filePath,
            basename($filePath),
            ['Content-Type' => 'application/pdf']
        );
    }

    public function verify(string $certificateNumber): JsonResponse
    {
        $result = $this->certificateService->verifyCertificate($certificateNumber);

        if (! $result) {
            return $this->error(__('api.certificates.certificate_not_found'), [], 404);
        }

        $certificate = $result['certificate'];

        return $this->success(__('api.certificates.certificate_verified'), [
            'valid' => $result['valid_signature'] && ! $result['is_expired'],
            'valid_signature' => $result['valid_signature'],
            'valid_pdf_signature' => $result['valid_pdf_signature'] ?? null,
            'is_expired' => $result['is_expired'],
            'certificate_number' => $certificate->certificate_number,
            'holder_name' => $certificate->certificate_data['holder_name'] ?? '',
            'plot_reference' => $certificate->certificate_data['plot_reference'] ?? '',
            'verdict' => $certificate->certificate_data['verdict'] ?? '',
            'pdf_content_hash' => $certificate->pdf_content_hash,
            'issued_at' => $certificate->issued_at?->toIso8601String(),
            'expires_at' => $certificate->expires_at?->toIso8601String(),
        ]);
    }

    public function list(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return $this->error(__('api.certificates.unauthenticated'), [], 401);
        }

        $certificates = VerificationCertificate::where('user_id', (int) $user->id)
            ->orderByDesc('issued_at')
            ->get()
            ->map(fn ($cert) => $this->certificatePayload($cert));

        return $this->success(__('api.certificates.list_fetched'), [
            'certificates' => $certificates,
        ]);
    }

    private function certificatePayload(VerificationCertificate $certificate): array
    {
        $fingerprint = null;
        try {
            $fingerprint = $this->certificateService->digitalSignatureService->getPublicKeyFingerprint();
        } catch (\Throwable) {
            $fingerprint = null;
        }

        return [
            'id' => $certificate->id,
            'certificate_number' => $certificate->certificate_number,
            'certificate_type' => $certificate->certificate_type
                ?? ($certificate->certificate_data['certificate_type'] ?? CertificateService::TYPE_BUYER),
            'certificate_title' => $certificate->certificate_data['certificate_title'] ?? null,
            'verification_log_id' => $certificate->verification_log_id,
            'plot_reference' => $certificate->certificate_data['plot_reference'] ?? '',
            'verdict' => $certificate->certificate_data['verdict'] ?? '',
            'risk_score' => $certificate->certificate_data['risk_score'] ?? null,
            'issued_at' => $certificate->issued_at?->toIso8601String(),
            'expires_at' => $certificate->expires_at?->toIso8601String(),
            'pdf_path' => $certificate->pdf_path,
            'download_available' => ! empty($certificate->pdf_path),
            'fingerprint' => $fingerprint,
            'pdf_content_hash' => $certificate->pdf_content_hash,
        ];
    }

    private function success(string $message, array $data = [], int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'errors' => (object) [],
            'timestamp' => now()->toIso8601String(),
        ], $status);
    }

    private function error(string $message, array $errors = [], int $status = 422): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => (object) [],
            'errors' => $errors,
            'timestamp' => now()->toIso8601String(),
        ], $status);
    }
}
