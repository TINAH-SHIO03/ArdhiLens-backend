<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Nida;
use App\Models\Plot;
use App\Models\PlotOwnershipHistory;
use App\Models\VerificationLog;
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

    private const CHALLENGE_TTL_MINUTES = 5;

    private const MAX_CHALLENGE_ATTEMPTS = 3;

    private const DEFAULT_MAX_DISTANCE_METERS = 150.0;

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
            ->where('plot_reference', $validated['plot_reference'])
            ->first();

        if (! $plot) {
            return $this->error(__('api.land_verification.plot_not_found'), [], 404);
        }

        $token = (string) Str::uuid();

        $session = [
            'user_id' => (int) $user->id,
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

        $distanceMeters = $this->haversineMeters(
            $submittedLatitude,
            $submittedLongitude,
            (float) $plot->gps_latitude,
            (float) $plot->gps_longitude,
        );

        $allowedDistanceMeters = (float) config('land_verification.max_distance_meters', self::DEFAULT_MAX_DISTANCE_METERS);
        $passed = $distanceMeters <= $allowedDistanceMeters;

        $session['steps']['geolocation_passed'] = $passed;
        $session['gps'] = [
            'passed' => $passed,
            'distance_meters' => round($distanceMeters, 2),
            'allowed_distance_meters' => $allowedDistanceMeters,
            'submitted_latitude' => $submittedLatitude,
            'submitted_longitude' => $submittedLongitude,
            'verified_at' => now()->toIso8601String(),
        ];
        $session['updated_at'] = now()->toIso8601String();
        $this->saveSession($token, $session);

        if (! $passed) {
            return $this->error(__('api.land_verification.gps_verification_failed'), [
                'gps_check' => $session['gps'],
            ], 422);
        }

        return $this->success(__('api.land_verification.gps_verification_passed'), [
            'gps_check' => $session['gps'],
            'next_step' => __('api.land_verification.next_steps.submit_nin'),
        ]);
    }

    public function generateNinQuestions(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'verification_token' => ['required', 'string'],
            'nin' => ['required', 'string', 'size:20'],
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
        GeminiLandAdvisoryService $geminiLandAdvisoryService
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

        if (! $questionsPassed) {
            $remainingAttempts = max(0, ($identity['max_attempts'] ?? self::MAX_CHALLENGE_ATTEMPTS) - $identity['attempts']);

            return $this->error(__('api.land_verification.identity_challenge_failed'), [
                'result' => [
                    'passed' => false,
                    'correct_count' => $correctCount,
                    'total_questions' => count($expected),
                    'remaining_attempts' => $remainingAttempts,
                ],
            ], 422);
        }

        $plot = Plot::query()->find($session['plot_id']);

        if (! $plot) {
            return $this->error(__('api.land_verification.plot_linked_not_found'), [], 404);
        }

        $nin = $identity['nin'];
        $ownerMatch = $plot->owner_nida === $nin;

        $latestTransfer = PlotOwnershipHistory::query()
            ->where('plot_id', $plot->id)
            ->orderByDesc('transfer_date')
            ->orderByDesc('id')
            ->first();

        $historyOwnerMatch = $latestTransfer ? $latestTransfer->to_nida === $nin : true;
        $ownerLinkPassed = $ownerMatch && $historyOwnerMatch;

        $session['steps']['owner_link_passed'] = $ownerLinkPassed;
        $session['updated_at'] = now()->toIso8601String();
        $this->saveSession($token, $session);

        if (! $ownerLinkPassed) {
            $forcedRiskScore = (int) config('land_verification.risk.owner_link_forced_score', 95);
            $ownerLinkReasons = [
                __('api.land_verification.assessment.owner_link.reasons.owner_mismatch'),
            ];

            if (! $historyOwnerMatch) {
                $ownerLinkReasons[] = __('api.land_verification.assessment.owner_link.reasons.history_mismatch');
            }

            $ownerLinkRecommendation = __('api.land_verification.assessment.owner_link.recommendation');
            $ownerLinkVerificationLogId = null;

            $userId = $session['user_id'] ?? null;
            if ($userId !== null) {
                $ownerLinkVerificationLog = VerificationLog::query()->create([
                    'user_id' => $userId,
                    'plot_id' => $plot->id,
                    'geolocation_passed' => (bool) ($session['steps']['geolocation_passed'] ?? false),
                    'nida_passed' => true,
                    'certificate_passed' => false,
                    'submitted_latitude' => $session['gps']['submitted_latitude'] ?? null,
                    'submitted_longitude' => $session['gps']['submitted_longitude'] ?? null,
                    'ai_verdict' => 'DO_NOT_BUY',
                    'risk_score' => $forcedRiskScore,
                    'ai_reasons' => $this->formatReasonsText($ownerLinkReasons),
                    'ai_recommendation' => $ownerLinkRecommendation,
                    'ai_payload' => [
                        'version' => 'risk-v1',
                        'plot_reference' => $plot->plot_reference,
                        'verification_context' => [
                            'gps_passed' => (bool) ($session['steps']['geolocation_passed'] ?? false),
                            'nida_passed' => true,
                            'owner_link_passed' => false,
                        ],
                        'steps' => [
                            'plot_found' => true,
                            'gps' => $session['gps'] ?? [],
                            'nin_found' => (bool) ($session['steps']['nin_found'] ?? false),
                            'questions_passed' => true,
                            'question_fields_used' => array_values($identity['selected_fields'] ?? []),
                            'owner_link' => [
                                'plot_owner_match' => $ownerMatch,
                                'history_owner_match' => $historyOwnerMatch,
                            ],
                        ],
                        'risk_engine' => [
                            'score' => $forcedRiskScore,
                            'verdict' => 'DO_NOT_BUY',
                            'verdict_label' => $this->verdictLabel('DO_NOT_BUY'),
                            'factors' => [
                                [
                                    'name' => 'owner_linkage_failure',
                                    'points' => $forcedRiskScore,
                                    'detail' => __('api.land_verification.assessment.owner_link.reasons.owner_mismatch'),
                                ],
                            ],
                            'interaction_penalties' => [],
                            'uncertainty_penalty' => 0,
                            'reasons' => $ownerLinkReasons,
                        ],
                        'gemini' => [
                            'model' => (string) config('services.gemini.model', 'gemini-2.0-flash'),
                            'prompt_version' => 'v1',
                            'parsed_ok' => false,
                            'fallback_used' => true,
                            'target_language' => app()->getLocale(),
                            'validation_errors' => ['Owner linkage failed before AI advisory call.'],
                            'raw_response' => null,
                        ],
                    ],
                    'status' => 'Failed',
                ]);

                $ownerLinkVerificationLogId = (int) $ownerLinkVerificationLog->id;
            }

            Cache::forget($this->sessionKey($token));

            return $this->error(__('api.land_verification.owner_linkage_failed'), [
                'verification_log_id' => $ownerLinkVerificationLogId,
                'owner_link' => [
                    'passed' => false,
                    'plot_owner_match' => $ownerMatch,
                    'history_owner_match' => $historyOwnerMatch,
                ],
                'assessment' => [
                    'verdict' => 'DO_NOT_BUY',
                    'verdict_label' => $this->verdictLabel('DO_NOT_BUY'),
                    'risk_score' => $forcedRiskScore,
                    'reasons' => $ownerLinkReasons,
                    'recommendation' => $ownerLinkRecommendation,
                ],
            ], 403);
        }

        $userId = $session['user_id'] ?? null;

        if ($userId === null) {
            return $this->error(__('api.land_verification.missing_user_id'), [], 422);
        }

        $nida = Nida::query()->where('nin', $nin)->first();
        $riskAssessment = $riskScoringService->score($plot);
        $verificationContext = [
            'gps_passed' => (bool) ($session['steps']['geolocation_passed'] ?? false),
            'nida_passed' => true,
            'owner_link_passed' => true,
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

        $verificationLog = VerificationLog::query()->create([
            'user_id' => $userId,
            'plot_id' => $plot->id,
            'geolocation_passed' => (bool) ($session['steps']['geolocation_passed'] ?? false),
            'nida_passed' => true,
            'certificate_passed' => true,
            'submitted_latitude' => $session['gps']['submitted_latitude'] ?? null,
            'submitted_longitude' => $session['gps']['submitted_longitude'] ?? null,
            'ai_verdict' => $aiAdvisory['verdict'],
            'risk_score' => $aiAdvisory['risk_score'],
            'ai_reasons' => $this->formatReasonsText($aiAdvisory['reasons']),
            'ai_recommendation' => $aiAdvisory['recommendation'],
            'ai_payload' => [
                'version' => 'risk-v1',
                'plot_reference' => $plot->plot_reference,
                'steps' => [
                    'plot_found' => true,
                    'gps' => $session['gps'] ?? [],
                    'nin_found' => (bool) ($session['steps']['nin_found'] ?? false),
                    'questions_passed' => true,
                    'question_fields_used' => array_values($identity['selected_fields'] ?? []),
                    'owner_link' => [
                        'plot_owner_match' => $ownerMatch,
                        'history_owner_match' => $historyOwnerMatch,
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
            'status' => 'Completed',
        ]);

        Cache::forget($this->sessionKey($token));

        return $this->success(__('api.land_verification.verification_completed'), [
            'verification_log_id' => $verificationLog->id,
            'plot_reference' => $plot->plot_reference,
            'identity' => [
                'full_name' => $nida?->full_name,
                'gender' => $nida?->gender,
                'nin_masked' => $this->maskNin($nin),
                'passport_image_url' => $this->passportImageUrl($nida),
            ],
            'steps' => [
                'plot_found' => true,
                'gps_passed' => true,
                'nida_questions_passed' => true,
                'owner_link_passed' => true,
            ],
            'assessment' => [
                'verdict' => $aiAdvisory['verdict'],
                'verdict_label' => $this->verdictLabel($aiAdvisory['verdict']),
                'risk_score' => $aiAdvisory['risk_score'],
                'reasons' => $aiAdvisory['reasons'],
                'recommendation' => $aiAdvisory['recommendation'],
            ],
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
            $questions[] = [
                'question_id' => $questionId,
                'prompt' => $this->questionPrompt($selected['field']),
                'type' => 'text',
            ];

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
