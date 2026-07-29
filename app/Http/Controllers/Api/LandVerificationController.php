<?php

namespace App\Http\Controllers\Api;

use App\Events\RiskScoreAlert;
use App\Events\VerificationCompleted;
use App\Http\Controllers\Controller;
use App\Models\Nida;
use App\Models\Plot;
use App\Models\PlotOwnershipHistory;
use App\Models\VerificationAttemptLog;
use App\Models\VerificationLog;
use App\Services\CertificateService;
use App\Services\LandVerification\GeminiLandAdvisoryService;
use App\Services\LandVerification\RiskScoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class LandVerificationController extends Controller
{
    private const CACHE_PREFIX = 'land-verification:';

    private const SESSION_TTL_MINUTES = 30;

    private const CHALLENGE_TTL_MINUTES = 10;

    private const MAX_CHALLENGE_ATTEMPTS = 3;

    private const DEFAULT_MAX_DISTANCE_METERS = 250.0;

    public function findPlot(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'plot_reference' => ['required', 'string', 'max:50'],
        ]);

        if ($validator->fails()) {
            return $this->error(__('api.land_verification.validation_failed'), [
                'validation' => $validator->errors()->toArray(),
            ], 422);
        }

        $validated = $validator->validated();
        $user = $request->user();

        if (! $user) {
            return $this->error(__('api.land_verification.unauthenticated'), [], 401);
        }

        $plot = Plot::query()
            ->whereRaw('LOWER(plot_reference) = ?', [strtolower($validated['plot_reference'])])
            ->first();

        if (! $plot) {
            return $this->error(__('api.land_verification.plot_not_found'), [], 404);
        }

        $token = (string) Str::uuid();

        $verificationMode = $user->isSeller() ? 'seller_ownership' : 'buyer_verification';

        if ($user->isSeller()) {
            $ownsPlot = $user->nin && $plot->owner_nida === $user->nin;
            if (! $ownsPlot) {
                return $this->error(
                    __('api.land_verification.seller_plot_not_linked'),
                    ['hint' => 'Complete KYC with the NIN registered as owner of this plot.'],
                    403
                );
            }
        }

        $session = [
            'user_id' => (int) $user->id,
            'user_role' => strtolower((string) $user->role),
            'verification_mode' => $verificationMode,
            'plot_id' => $plot->id,
            'plot_reference' => $plot->plot_reference,
            'steps' => [
                'plot_found' => true,
                'geolocation_passed' => false,
                'nin_found' => false,
                'nida_passed' => false,
                'owner_link_passed' => false,
            ],
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ];

        $this->saveSession($token, $session);

        return $this->success(__('api.land_verification.plot_found'), [
            'verification_token' => $token,
            'plot' => $this->plotResponse($plot),
            'next_step' => __('api.land_verification.next_steps.submit_gps'),
        ]);
    }

    public function verifyGps(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'verification_token' => ['required', 'string'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy_meters' => ['nullable', 'numeric', 'min:0', 'max:5000'],
            'altitude' => ['nullable', 'numeric'],
            'speed_mps' => ['nullable', 'numeric', 'min:0'],
            // remote (default): verify any plot without requiring the user to be there
            // on_site: hard proximity gate for field audits
            'mode' => ['nullable', 'string', 'in:remote,on_site'],
        ]);

        if ($validator->fails()) {
            return $this->error(__('api.land_verification.validation_failed'), [
                'validation' => $validator->errors()->toArray(),
            ], 422);
        }

        $validated = $validator->validated();
        $token = $validated['verification_token'];
        $mode = $validated['mode'] ?? 'remote';
        $session = $this->getSession($token);

        if (! $session) {
            return $this->error(__('api.land_verification.session_not_found_or_expired'), [], 404);
        }

        $plot = Plot::query()->find($session['plot_id']);

        if (! $plot) {
            return $this->error(__('api.land_verification.plot_linked_not_found'), [], 404);
        }

        if ($plot->gps_latitude === null || $plot->gps_longitude === null) {
            $session['steps']['geolocation_passed'] = false;
            $session['gps'] = [
                'passed' => false,
                'reason' => __('api.land_verification.gps.plot_coordinates_missing'),
            ];
            $session['updated_at'] = now()->toIso8601String();
            $this->saveSession($token, $session);

            return $this->error(__('api.land_verification.plot_coordinates_unavailable'), [
                'gps_check' => $session['gps'],
            ], 422);
        }

        $submittedLatitude = (float) $validated['latitude'];
        $submittedLongitude = (float) $validated['longitude'];

        $geo = app(\App\Services\GeospatialService::class)->verifyLocation(
            $plot,
            $submittedLatitude,
            $submittedLongitude,
            isset($validated['accuracy_meters']) ? (float) $validated['accuracy_meters'] : null,
            isset($validated['altitude']) ? (float) $validated['altitude'] : null,
            isset($validated['speed_mps']) ? (float) $validated['speed_mps'] : null,
        );

        $nearPlot = (bool) $geo['passed'];

        if ($mode === 'remote') {
            // Always allow continuing; classify presence for trust label only.
            $passed = true;
            $verificationMode = $nearPlot ? 'verified_on_site' : 'remote_check';
            $geo['passed'] = true;
            $geo['proximity_passed'] = $nearPlot;
            $geo['verification_mode'] = $verificationMode;
            $geo['mode'] = 'remote';
        } else {
            $passed = $nearPlot;
            $verificationMode = $nearPlot ? 'verified_on_site' : 'on_site_failed';
            $geo['proximity_passed'] = $nearPlot;
            $geo['verification_mode'] = $verificationMode;
            $geo['mode'] = 'on_site';
        }

        $session['steps']['geolocation_passed'] = $passed;
        $session['gps'] = array_merge($geo, [
            'submitted_latitude' => $submittedLatitude,
            'submitted_longitude' => $submittedLongitude,
            'verified_at' => now()->toIso8601String(),
        ]);
        $session['updated_at'] = now()->toIso8601String();
        $this->saveSession($token, $session);

        if (! $passed) {
            return $this->error(__('api.land_verification.gps_verification_failed'), [
                'gps_check' => $session['gps'],
            ], 422);
        }

        $message = $verificationMode === 'verified_on_site'
            ? __('api.land_verification.gps_verification_on_site')
            : __('api.land_verification.gps_verification_remote');

        return $this->success($message, [
            'gps_check' => $session['gps'],
            'next_step' => __('api.land_verification.next_steps.submit_nin'),
        ]);
    }

    public function generateNinQuestions(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'verification_token' => ['required', 'string'],
            'nin' => ['required', 'string', 'size:20', 'regex:/^\d{8}-\d{5}-\d{5}$/'],
        ]);

        if ($validator->fails()) {
            return $this->error(__('api.land_verification.validation_failed'), [
                'validation' => $validator->errors()->toArray(),
            ], 422);
        }

        $validated = $validator->validated();
        $token = $validated['verification_token'];
        $session = $this->getSession($token);

        if (! $session) {
            return $this->error(__('api.land_verification.session_not_found_or_expired'), [], 404);
        }

        if (($session['steps']['geolocation_passed'] ?? false) !== true) {
            return $this->error(__('api.land_verification.gps_must_pass_before_nin_challenge'), [], 409);
        }

        $nin = trim($validated['nin']);

        $nida = Nida::query()
            ->where('nin', $nin)
            ->where('status', 'Active')
            ->first();

        if (! $nida) {
            return $this->error(__('api.land_verification.unable_to_generate_identity_questions'), [], 404);
        }

        $questionSet = $this->buildDynamicQuestionSet($nida);

        if ($questionSet === null) {
            return $this->error(__('api.land_verification.insufficient_nida_data'), [], 422);
        }

        $challengeId = (string) Str::uuid();

        $session['steps']['nin_found'] = true;
        $session['steps']['nida_passed'] = false;
        $session['steps']['owner_link_passed'] = false;
        $session['identity'] = [
            'nin' => $nin,
            'challenge_id' => $challengeId,
            'questions' => $questionSet['questions'],
            'expected' => $questionSet['expected'],
            'selected_fields' => $questionSet['selected_fields'],
            'attempts' => 0,
            'max_attempts' => self::MAX_CHALLENGE_ATTEMPTS,
            'issued_at' => now()->toIso8601String(),
            'expires_at' => now()->addMinutes(self::CHALLENGE_TTL_MINUTES)->toIso8601String(),
        ];
        $session['updated_at'] = now()->toIso8601String();
        $this->saveSession($token, $session);

        return $this->success(__('api.land_verification.dynamic_questions_generated'), [
            'challenge_id' => $challengeId,
            'expires_at' => $session['identity']['expires_at'],
            'questions' => $questionSet['questions'],
            'next_step' => __('api.land_verification.next_steps.submit_answers'),
        ]);
    }

    public function verifyNinAnswers(
        Request $request,
        RiskScoringService $riskScoringService,
        GeminiLandAdvisoryService $geminiLandAdvisoryService,
        CertificateService $certificateService
    ): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'verification_token' => ['required', 'string'],
            'challenge_id' => ['required', 'uuid'],
            'answers' => ['required', 'array', 'size:3'],
            'answers.*.question_id' => ['required', 'string'],
            'answers.*.answer' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return $this->error(__('api.land_verification.validation_failed'), [
                'validation' => $validator->errors()->toArray(),
            ], 422);
        }

        $validated = $validator->validated();
        $token = $validated['verification_token'];
        $user = $request->user();

        if (! $user) {
            return $this->error(__('api.land_verification.unauthenticated'), [], 401);
        }

        $session = $this->getSession($token);

        if (! $session) {
            return $this->error(__('api.land_verification.session_not_found_or_expired'), [], 404);
        }

        $identity = $session['identity'] ?? null;

        if (! is_array($identity)) {
            return $this->error(__('api.land_verification.identity_challenge_not_generated'), [], 409);
        }

        if (($session['steps']['geolocation_passed'] ?? false) !== true) {
            return $this->error(__('api.land_verification.gps_must_pass_before_answer_verification'), [], 409);
        }

        if (($identity['challenge_id'] ?? null) !== $validated['challenge_id']) {
            return $this->error(__('api.land_verification.invalid_challenge_id'), [], 422);
        }

        if (Carbon::parse($identity['expires_at'])->isPast()) {
            return $this->error(__('api.land_verification.challenge_expired'), [], 410);
        }

        if (($identity['attempts'] ?? 0) >= ($identity['max_attempts'] ?? self::MAX_CHALLENGE_ATTEMPTS)) {
            return $this->error(__('api.land_verification.maximum_attempts_reached'), [], 429);
        }

        $expected = $identity['expected'] ?? [];

        if (count($expected) !== 3) {
            return $this->error(__('api.land_verification.challenge_data_invalid'), [], 422);
        }

        $submittedAnswers = [];
        foreach ($validated['answers'] as $item) {
            $submittedAnswers[$item['question_id']] = $item['answer'];
        }

        $correctCount = 0;
        foreach ($expected as $questionId => $meta) {
            $submitted = $submittedAnswers[$questionId] ?? null;
            $isCorrect = is_string($submitted)
                && hash_equals($meta['answer_hash'], $this->hashAnswer($submitted));

            if ($isCorrect) {
                $correctCount++;
            }
        }

        $identity['attempts'] = ($identity['attempts'] ?? 0) + 1;
        $identity['last_result'] = [
            'correct_count' => $correctCount,
            'total_questions' => count($expected),
            'checked_at' => now()->toIso8601String(),
        ];

        $questionsPassed = $correctCount === count($expected);
        $session['steps']['nida_passed'] = $questionsPassed;
        $session['identity'] = $identity;
        $session['updated_at'] = now()->toIso8601String();
        $this->saveSession($token, $session);

        $remainingAttempts = max(0, ($identity['max_attempts'] ?? self::MAX_CHALLENGE_ATTEMPTS) - $identity['attempts']);

        VerificationAttemptLog::logAttempt(
            $session['user_id'] ?? null,
            $token,
            $validated['challenge_id'] ?? null,
            'nin_challenge',
            $questionsPassed,
            $correctCount,
            count($expected),
        );

        $plot = Plot::query()->find($session['plot_id']);

        if (! $plot) {
            return $this->error(__('api.land_verification.plot_linked_not_found'), [], 404);
        }

        $nin = $identity['nin'];
        $ownerMatch = false;
        $historyOwnerMatch = false;
        $ownerLinkPassed = false;

        if ($questionsPassed) {
            // Owner registry match is informational only — verification is not
            // blocked by linking a specific NIN to a specific plot.
            $ownerMatch = $plot->owner_nida !== null && $plot->owner_nida === $nin;

            $latestTransfer = PlotOwnershipHistory::query()
                ->where('plot_id', $plot->id)
                ->orderByDesc('transfer_date')
                ->orderByDesc('id')
                ->first();

            $historyOwnerMatch = $latestTransfer ? $latestTransfer->to_nida === $nin : true;
            $ownerLinkPassed = $ownerMatch && $historyOwnerMatch;

            $session['steps']['owner_link_passed'] = $ownerLinkPassed;
            $session['submitted_nin'] = $nin;
            $session['updated_at'] = now()->toIso8601String();
            $this->saveSession($token, $session);
        }

        // Continue to full risk scoring + certificate even when NIN ≠ plot owner.
        $nida = Nida::query()->where('nin', $nin)->first();
        $riskAssessment = $riskScoringService->score($plot);
        $verificationContext = [
            'gps_passed' => (bool) ($session['steps']['geolocation_passed'] ?? false),
            'nida_passed' => $questionsPassed,
            'owner_link_passed' => $ownerLinkPassed,
        ];
        $landData = $plot->getAiPayload();
        $aiAdvisory = $geminiLandAdvisoryService->advise([
            'system_risk_score' => $riskAssessment['score'],
            'system_verdict' => $riskAssessment['verdict'],
            'response_language' => app()->getLocale(),
            'risk_breakdown' => [
                'factors' => $riskAssessment['factors'],
                'interaction_penalties' => $riskAssessment['interaction_penalties'],
                'uncertainty_penalty' => $riskAssessment['uncertainty_penalty'],
                'reasons' => $riskAssessment['reasons'],
            ],
            'land_data' => $landData,
            'verification_context' => $verificationContext,
        ]);

        // Always keep verdict aligned with the system risk engine score.
        $finalVerdict = $riskAssessment['verdict'];
        $finalRiskScore = (int) $riskAssessment['score'];
        $aiAdvisory['verdict'] = $finalVerdict;
        $aiAdvisory['risk_score'] = $finalRiskScore;

        $logStatus = 'Completed';

        if (! $questionsPassed) {
            $finalVerdict = 'DO_NOT_BUY';
            $cautionMax = (int) config('land_verification.risk.thresholds.caution_max', 69);
            $finalRiskScore = max($finalRiskScore, $cautionMax + 1);
            $aiAdvisory['verdict'] = $finalVerdict;
            $aiAdvisory['risk_score'] = $finalRiskScore;
        }

        $verificationLog = VerificationLog::query()->create([
            'user_id' => $user->id,
            'plot_id' => $plot->id,
            'geolocation_passed' => (bool) ($session['steps']['geolocation_passed'] ?? false),
            'nida_passed' => $questionsPassed,
            'certificate_passed' => $questionsPassed,
            'submitted_latitude' => $session['gps']['submitted_latitude'] ?? null,
            'submitted_longitude' => $session['gps']['submitted_longitude'] ?? null,
            'ai_verdict' => $finalVerdict,
            'risk_score' => $aiAdvisory['risk_score'],
            'ai_reasons' => $this->formatReasonsText($aiAdvisory['reasons']),
            'ai_recommendation' => $aiAdvisory['recommendation'],
            'ai_payload' => [
                'version' => 'risk-v1',
                'plot_reference' => $plot->plot_reference,
                'submitted_nin' => $nin,
                'steps' => [
                    'plot_found' => true,
                    'gps' => $session['gps'] ?? [],
                    'nin_found' => (bool) ($session['steps']['nin_found'] ?? false),
                    'questions_passed' => $questionsPassed,
                    'question_fields_used' => array_values($identity['selected_fields'] ?? []),
                    'owner_link' => [
                        'plot_owner_match' => $ownerMatch,
                        'history_owner_match' => $historyOwnerMatch,
                        'hard_gate' => false,
                    ],
                ],
                'verification_context' => $verificationContext,
                'risk_engine' => [
                    'score' => $riskAssessment['score'],
                    'verdict' => $riskAssessment['verdict'],
                    'verdict_label' => $this->verdictLabel($riskAssessment['verdict']),
                    'factors' => $riskAssessment['factors'],
                    'interaction_penalties' => $riskAssessment['interaction_penalties'],
                    'uncertainty_penalty' => $riskAssessment['uncertainty_penalty'],
                    'reasons' => $riskAssessment['reasons'],
                ],
                'gemini' => $aiAdvisory['gemini'] ?? [],
                'land_data' => $landData,
            ],
            'status' => $logStatus,
        ]);

        Cache::forget($this->sessionKey($token));

        VerificationCompleted::dispatch($user, $verificationLog, $plot);

        $riskAlertThreshold = (int) config('notifications.risk_alert_threshold', 30);
        if ($verificationLog->risk_score >= $riskAlertThreshold) {
            RiskScoreAlert::dispatch($user, $verificationLog, $plot, $verificationLog->risk_score, $verificationLog->ai_verdict);
        }

        $certificate = null;
        $certificateError = null;
        $verificationMode = $session['verification_mode'] ?? 'buyer_verification';
        $isSellerMode = $verificationMode === 'seller_ownership';

        $eligibleForCertificate = $questionsPassed && (
            $isSellerMode
                ? $ownerLinkPassed && in_array($verificationLog->ai_verdict, ['SAFE', 'CAUTION'], true)
                : in_array($verificationLog->ai_verdict, ['SAFE', 'CAUTION'], true)
        );

        if ($eligibleForCertificate) {
            try {
                $certificate = \App\Models\VerificationCertificate::query()
                    ->where('verification_log_id', $verificationLog->id)
                    ->first();

                $certType = $isSellerMode
                    ? CertificateService::TYPE_SELLER
                    : CertificateService::TYPE_BUYER;

                if (! $certificate) {
                    $certificate = $certificateService->generateCertificate(
                        $user,
                        $verificationLog,
                        $plot,
                        $certType
                    );
                }

                try {
                    if (! $certificate->pdf_path || ! \Illuminate\Support\Facades\Storage::disk('local')->exists($certificate->pdf_path)) {
                        $certificateService->generatePdf($certificate);
                        $certificate->refresh();
                    }
                } catch (\Throwable $pdfError) {
                    $certificateError = 'PDF fingerprint document failed: '.$pdfError->getMessage();
                    \Illuminate\Support\Facades\Log::error('Certificate PDF generation failed', [
                        'verification_log_id' => $verificationLog->id,
                        'certificate_id' => $certificate->id,
                        'error' => $pdfError->getMessage(),
                    ]);
                }
            } catch (\Throwable $e) {
                $certificateError = $e->getMessage();
                \Illuminate\Support\Facades\Log::error('Certificate generation failed', [
                    'verification_log_id' => $verificationLog->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $fingerprint = null;
        if ($certificate) {
            try {
                $fingerprint = $certificateService->digitalSignatureService->getPublicKeyFingerprint();
            } catch (\Throwable) {
                $fingerprint = null;
            }
        }

        return $this->success(__('api.land_verification.verification_completed'), [
            'verification_log_id' => $verificationLog->id,
            'verification_mode' => $verificationMode,
            'plot_reference' => $plot->plot_reference,
            'identity' => $questionsPassed ? [
                'full_name' => $nida?->full_name,
                'gender' => $nida?->gender,
                'nin_masked' => $this->maskNin($nin),
                'nin' => $nin,
                'passport_image_url' => $this->passportImageUrl($nida),
            ] : null,
            'steps' => [
                'plot_found' => true,
                'gps_passed' => (bool) ($session['steps']['geolocation_passed'] ?? false),
                'nida_questions_passed' => $questionsPassed,
                'owner_link_passed' => $ownerLinkPassed,
            ],
            'assessment' => [
                'verdict' => $finalVerdict,
                'verdict_label' => $this->verdictLabel($finalVerdict),
                'risk_score' => $aiAdvisory['risk_score'],
                'reasons' => $aiAdvisory['reasons'],
                'recommendation' => $aiAdvisory['recommendation'],
            ],
            'remaining_attempts' => $remainingAttempts,
            'certificate_eligible' => $eligibleForCertificate,
            'certificate_error' => $certificateError,
            'certificate' => $certificate ? [
                'id' => $certificate->id,
                'certificate_number' => $certificate->certificate_number,
                'certificate_type' => $certificate->certificate_type
                    ?? ($certificate->certificate_data['certificate_type'] ?? 'buyer_verification'),
                'certificate_title' => $certificate->certificate_data['certificate_title'] ?? null,
                'issued_at' => $certificate->issued_at?->toIso8601String(),
                'verdict' => $certificate->certificate_data['verdict'] ?? $verificationLog->ai_verdict,
                'risk_score' => $certificate->certificate_data['risk_score'] ?? $verificationLog->risk_score,
                'download_available' => ! empty($certificate->pdf_path),
                'fingerprint' => $fingerprint,
                'pdf_content_hash' => $certificate->pdf_content_hash,
            ] : null,
        ]);
    }

    public function explainAssessment(Request $request, GeminiLandAdvisoryService $geminiLandAdvisoryService): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'verification_log_id' => ['required', 'integer'],
            'question' => ['nullable', 'string', 'max:2000'],
            'conversation_history' => ['nullable', 'array', 'max:12'],
            'conversation_history.*.role' => ['required_with:conversation_history', 'string', 'in:user,assistant'],
            'conversation_history.*.text' => ['required_with:conversation_history', 'string', 'max:2000'],
        ]);

        if ($validator->fails()) {
            return $this->error(__('api.land_verification.validation_failed'), [
                'validation' => $validator->errors()->toArray(),
            ], 422);
        }

        $user = $request->user();
        if (! $user) {
            return $this->error(__('api.land_verification.unauthenticated'), [], 401);
        }

        $validated = $validator->validated();

        $verificationLog = VerificationLog::query()
            ->where('id', (int) $validated['verification_log_id'])
            ->where('user_id', (int) $user->id)
            ->first();

        if (! $verificationLog) {
            return $this->error(__('api.land_verification.assistant.verification_log_not_found'), [], 404);
        }

        $verdict = strtoupper((string) ($verificationLog->ai_verdict ?? 'INCOMPLETE'));
        $riskScore = (int) ($verificationLog->risk_score ?? 0);
        $reasons = $this->extractAssessmentReasons($verificationLog);
        $recommendation = trim((string) ($verificationLog->ai_recommendation ?? ''));
        $question = trim((string) ($validated['question'] ?? ''));
        $conversationHistory = $this->normalizeConversationHistory($validated['conversation_history'] ?? []);

        $plotReference = '';
        if (is_array($verificationLog->ai_payload)) {
            $plotReference = (string) ($verificationLog->ai_payload['plot_reference'] ?? '');
        }

        $aiResponse = $geminiLandAdvisoryService->explainForBuyer([
            'verdict' => $verdict,
            'verdict_label' => $this->verdictLabel($verdict),
            'risk_score' => $riskScore,
            'reasons' => $reasons,
            'recommendation' => $recommendation,
            'question' => $question,
            'response_language' => app()->getLocale(),
            'plot_reference' => $plotReference,
            'verification_context' => is_array($verificationLog->ai_payload)
                ? ($verificationLog->ai_payload['verification_context'] ?? [])
                : [],
            'risk_engine' => is_array($verificationLog->ai_payload)
                ? ($verificationLog->ai_payload['risk_engine'] ?? [])
                : [],
            'conversation_history' => $conversationHistory,
        ]);

        return $this->success(__('api.land_verification.assistant.explained'), [
            'verification_log_id' => $verificationLog->id,
            'assistant' => [
                'related' => (bool) ($aiResponse['related'] ?? true),
                'answer' => $aiResponse['answer'],
                'recommended_action' => (string) ($aiResponse['recommended_action'] ?? ''),
                'suggested_next_steps' => $aiResponse['suggested_next_steps'],
            ],
            'assessment' => [
                'verdict' => $verdict,
                'verdict_label' => $this->verdictLabel($verdict),
                'risk_score' => $riskScore,
            ],
            'gemini' => $aiResponse['gemini'] ?? [],
        ]);
    }

    private function buildDynamicQuestionSet(Nida $nida): ?array
    {
        $motherField = $this->choosePreferredField(
            'mother_middle_name',
            $nida->mother_middle_name,
            'mother_surname',
            $nida->mother_surname,
        );

        $fatherField = $this->choosePreferredField(
            'father_middle_name',
            $nida->father_middle_name,
            'father_surname',
            $nida->father_surname,
        );

        $locationField = $this->pickRandomLocationField($nida);

        if ($motherField === null || $fatherField === null || $locationField === null) {
            return null;
        }

        $questions = [];
        $expected = [];
        $selectedFields = [];

        foreach ([$motherField, $fatherField, $locationField] as $selected) {
            $questionId = 'q_'.Str::lower(Str::random(12));
            $question = [
                'question_id' => $questionId,
                'prompt' => $this->questionPrompt($selected['field']),
                'type' => 'text',
                'field' => $selected['field'],
            ];

            if (config('land_verification.demo_hints', true)) {
                $question['demo_answer'] = (string) $selected['value'];
            }

            $questions[] = $question;

            $expected[$questionId] = [
                'field' => $selected['field'],
                'answer_hash' => $this->hashAnswer($selected['value']),
            ];

            $selectedFields[] = $selected['field'];
        }

        return [
            'questions' => $questions,
            'expected' => $expected,
            'selected_fields' => $selectedFields,
        ];
    }

    private function choosePreferredField(string $preferredField, ?string $preferredValue, string $fallbackField, ?string $fallbackValue): ?array
    {
        $preferred = $this->normalizeAnswer($preferredValue);
        $fallback = $this->normalizeAnswer($fallbackValue);

        if ($preferred === '' && $fallback === '') {
            return null;
        }

        if ($preferred === '') {
            return [
                'field' => $fallbackField,
                'value' => $fallbackValue,
            ];
        }

        if ($fallback === '') {
            return [
                'field' => $preferredField,
                'value' => $preferredValue,
            ];
        }

        // Middle names stay preferred but still dynamic.
        if (random_int(1, 100) <= 70) {
            return [
                'field' => $preferredField,
                'value' => $preferredValue,
            ];
        }

        return [
            'field' => $fallbackField,
            'value' => $fallbackValue,
        ];
    }

    private function pickRandomLocationField(Nida $nida): ?array
    {
        $candidates = [
            'perm_ward' => $nida->perm_ward,
            'perm_mtaa' => $nida->perm_mtaa,
            'perm_district' => $nida->perm_district,
            'res_ward' => $nida->res_ward,
            'res_mtaa' => $nida->res_mtaa,
            'res_district' => $nida->res_district,
        ];

        $available = [];
        foreach ($candidates as $field => $value) {
            if ($this->normalizeAnswer($value) !== '') {
                $available[] = [
                    'field' => $field,
                    'value' => $value,
                ];
            }
        }

        if ($available === []) {
            return null;
        }

        return $available[array_rand($available)];
    }

    private function questionPrompt(string $field): string
    {
        $pool = trans('api.land_verification.prompts.'.$field);

        if (! is_array($pool) || $pool === []) {
            $pool = trans('api.land_verification.prompts.identity_detail');
        }

        if (! is_array($pool) || $pool === []) {
            $pool = ['Enter the requested identity detail.'];
        }

        return $pool[array_rand($pool)];
    }

    private function plotResponse(Plot $plot): array
    {
        return [
            'id' => $plot->id,
            'plot_reference' => $plot->plot_reference,
            'region' => $plot->region,
            'district' => $plot->district,
            'ward' => $plot->ward,
            'village_mtaa' => $plot->village_mtaa,
            'street' => $plot->street,
            'gps_latitude' => $plot->gps_latitude,
            'gps_longitude' => $plot->gps_longitude,
            'size_hectares' => $plot->size_hectares,
            'land_use' => $plot->land_use,
            'tenure_type' => $plot->tenure_type,
            'certificate_type' => $plot->certificate_type,
            'issue_date' => $plot->issue_date?->toDateString(),
            'expiry_date' => $plot->expiry_date?->toDateString(),
            'status' => $plot->status,
        ];
    }

    private function getSession(string $token): ?array
    {
        $session = Cache::get($this->sessionKey($token));

        return is_array($session) ? $session : null;
    }

    private function saveSession(string $token, array $session): void
    {
        Cache::put(
            $this->sessionKey($token),
            $session,
            now()->addMinutes(self::SESSION_TTL_MINUTES),
        );
    }

    private function sessionKey(string $token): string
    {
        return self::CACHE_PREFIX.$token;
    }

    private function normalizeAnswer(?string $value): string
    {
        $normalized = strtolower((string) $value);
        $normalized = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $normalized) ?? '';
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? '';

        return trim($normalized);
    }

    private function hashAnswer(?string $value): string
    {
        return hash('sha256', $this->normalizeAnswer((string) $value));
    }

    private function maskNin(?string $nin): ?string
    {
        if ($nin === null) {
            return null;
        }

        $nin = trim($nin);

        if (strlen($nin) <= 8) {
            return str_repeat('*', strlen($nin));
        }

        return substr($nin, 0, 4).str_repeat('*', strlen($nin) - 8).substr($nin, -4);
    }

    private function passportImageUrl(?Nida $nida): ?string
    {
        if (! $nida || ! $nida->passport_image_path) {
            return null;
        }

        return Storage::disk('public')->url($nida->passport_image_path);
    }

    private function formatReasonsText(array $reasons): string
    {
        $normalized = [];

        foreach ($reasons as $reason) {
            $line = trim((string) $reason);
            if ($line !== '') {
                $normalized[] = $line;
            }
        }

        if ($normalized === []) {
            return '';
        }

        return implode("\n", array_values(array_unique($normalized)));
    }

    private function extractAssessmentReasons(VerificationLog $verificationLog): array
    {
        $reasons = [];

        if (is_array($verificationLog->ai_payload)) {
            $payloadReasons = $verificationLog->ai_payload['risk_engine']['reasons'] ?? null;
            if (is_array($payloadReasons)) {
                $reasons = $payloadReasons;
            }
        }

        if ($reasons === []) {
            $rawReasons = trim((string) ($verificationLog->ai_reasons ?? ''));
            if ($rawReasons !== '') {
                $reasons = preg_split('/\r\n|\r|\n/', $rawReasons) ?: [];
            }
        }

        $normalized = [];
        foreach ($reasons as $reason) {
            $line = trim((string) $reason);
            if ($line !== '') {
                $normalized[] = $line;
            }
        }

        if ($normalized === []) {
            return [(string) __('api.land_verification.assistant.default_reason')];
        }

        return array_values(array_unique($normalized));
    }

    private function normalizeConversationHistory(mixed $history): array
    {
        if (! is_array($history)) {
            return [];
        }

        $normalized = [];

        foreach ($history as $item) {
            if (! is_array($item)) {
                continue;
            }

            $role = strtolower(trim((string) ($item['role'] ?? '')));
            if (! in_array($role, ['user', 'assistant'], true)) {
                continue;
            }

            $text = trim((string) ($item['text'] ?? ''));
            if ($text === '') {
                continue;
            }

            $normalized[] = [
                'role' => $role,
                'text' => Str::limit($text, 2000, ''),
            ];
        }

        if ($normalized === []) {
            return [];
        }

        return array_slice($normalized, -10);
    }

    private function verdictLabel(string $verdict): string
    {
        return match (strtoupper(trim($verdict))) {
            'SAFE' => __('api.land_verification.assessment.verdict_labels.safe'),
            'CAUTION' => __('api.land_verification.assessment.verdict_labels.caution'),
            'DO_NOT_BUY' => __('api.land_verification.assessment.verdict_labels.do_not_buy'),
            default => strtoupper(trim($verdict)),
        };
    }

    private function haversineMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadiusMeters = 6371000.0;

        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLon = deg2rad($lon2 - $lon1);

        $a = sin($deltaLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($deltaLon / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadiusMeters * $c;
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
