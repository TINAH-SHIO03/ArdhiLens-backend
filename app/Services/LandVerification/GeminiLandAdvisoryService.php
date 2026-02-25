<?php

namespace App\Services\LandVerification;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class GeminiLandAdvisoryService
{
    private const PROMPT_VERSION = 'v2';

    private const LANG_EN = 'en';

    private const LANG_SW = 'sw';

    /**
     * @return array{
     *   verdict: string,
     *   risk_score: int,
     *   reasons: array<int, string>,
     *   recommendation: string,
     *   gemini: array<string, mixed>
     * }
     */
    public function advise(array $payload): array
    {
        $systemVerdict = strtoupper((string) ($payload['system_verdict'] ?? 'INCOMPLETE'));
        $systemRiskScore = (int) ($payload['system_risk_score'] ?? 0);
        $responseLanguage = $this->normalizeLanguage((string) ($payload['response_language'] ?? app()->getLocale() ?? self::LANG_EN));

        $fallbackReasons = $this->normalizeReasons($payload['risk_breakdown']['reasons'] ?? []);

        $runtime = $this->runtimeConfig();

        if ($runtime['api_key'] === '') {
            return $this->fallback(
                $systemVerdict,
                $systemRiskScore,
                $fallbackReasons,
                ['Gemini API key is missing.'],
                null,
                false,
                $runtime['model'],
                $responseLanguage
            );
        }

        $contradictionCheck = $this->detectContradictions(
            $payload,
            $runtime,
            $responseLanguage
        );

        $fallbackReasons = $this->normalizeReasons(array_merge(
            $fallbackReasons,
            $this->contradictionReasons($contradictionCheck['items'] ?? [])
        ));

        $prompt = $this->buildPrompt(
            $payload,
            $responseLanguage,
            $contradictionCheck['items'] ?? [],
            (string) ($contradictionCheck['summary'] ?? '')
        );

        $result = $this->requestModelJson($prompt, $runtime);

        if (! $result['ok']) {
            return $this->fallback(
                $systemVerdict,
                $systemRiskScore,
                $fallbackReasons,
                [$result['error']],
                $result['raw_response'],
                false,
                $runtime['model'],
                $responseLanguage,
                $contradictionCheck
            );
        }

        $decoded = $this->decodeJsonStrict((string) $result['text']);
        if (! is_array($decoded)) {
            return $this->fallback(
                $systemVerdict,
                $systemRiskScore,
                $fallbackReasons,
                ['Gemini returned non-JSON or invalid JSON content.'],
                $result['raw_response'],
                false,
                $runtime['model'],
                $responseLanguage,
                $contradictionCheck
            );
        }

        $validationErrors = $this->validateResponse($decoded, $systemVerdict, $systemRiskScore);
        if ($validationErrors !== []) {
            return $this->fallback(
                $systemVerdict,
                $systemRiskScore,
                $fallbackReasons,
                $validationErrors,
                $result['raw_response'],
                false,
                $runtime['model'],
                $responseLanguage,
                $contradictionCheck
            );
        }

        return [
            'verdict' => $systemVerdict,
            'risk_score' => $systemRiskScore,
            'reasons' => $this->normalizeReasons($decoded['reasons']),
            'recommendation' => $this->normalizeRecommendationByVerdict(
                trim((string) $decoded['recommendation']),
                $systemVerdict,
                $responseLanguage
            ),
            'gemini' => [
                'model' => $runtime['model'],
                'prompt_version' => self::PROMPT_VERSION,
                'parsed_ok' => true,
                'fallback_used' => false,
                'validation_errors' => [],
                'target_language' => $responseLanguage,
                'raw_response' => $result['raw_response'],
                'contradiction_check' => $this->compactContradictionMeta($contradictionCheck),
            ],
        ];
    }

    /**
     * @return array{
     *   answer: string,
     *   suggested_next_steps: array<int, string>,
     *   gemini: array<string, mixed>
     * }
     */
    public function explainForBuyer(array $payload): array
    {
        $responseLanguage = $this->normalizeLanguage((string) ($payload['response_language'] ?? app()->getLocale() ?? self::LANG_EN));
        $question = trim((string) ($payload['question'] ?? ''));
        $verdict = strtoupper((string) ($payload['verdict'] ?? 'INCOMPLETE'));
        $riskScore = (int) ($payload['risk_score'] ?? 0);
        $reasons = $this->normalizeReasons($payload['reasons'] ?? []);
        $recommendation = trim((string) ($payload['recommendation'] ?? ''));
        $verdictLabel = trim((string) ($payload['verdict_label'] ?? ''));
        $verificationContext = $payload['verification_context'] ?? [];
        $riskEngine = $payload['risk_engine'] ?? [];
        $plotReference = trim((string) ($payload['plot_reference'] ?? ''));
        $conversationHistory = $this->normalizeConversationHistory($payload['conversation_history'] ?? []);

        if ($question === '') {
            $question = $responseLanguage === self::LANG_SW
                ? 'Nifafanulie matokeo haya kwa kina kama mnunuzi na nielekeze hatua za kuchukua.'
                : 'Please explain this result in detail for a buyer and what actions should be taken next.';
        }

        $runtime = $this->runtimeConfig();
        $fallbackAnswer = $this->buildChatFallbackAnswer(
            $verdict,
            $verdictLabel,
            $riskScore,
            $reasons,
            $recommendation,
            $responseLanguage
        );

        if ($runtime['api_key'] === '') {
            return [
                'answer' => $fallbackAnswer,
                'suggested_next_steps' => $this->fallbackChatSteps($verdict, $responseLanguage),
                'gemini' => [
                    'model' => $runtime['model'],
                    'prompt_version' => self::PROMPT_VERSION,
                    'parsed_ok' => false,
                    'fallback_used' => true,
                    'validation_errors' => ['Gemini API key is missing.'],
                    'target_language' => $responseLanguage,
                    'raw_response' => null,
                ],
            ];
        }

        $prompt = $this->buildChatPrompt(
            $question,
            $conversationHistory,
            $verdict,
            $verdictLabel,
            $riskScore,
            $reasons,
            $recommendation,
            $verificationContext,
            $riskEngine,
            $plotReference,
            $responseLanguage
        );

        $result = $this->requestModelJson($prompt, $runtime);
        if (! $result['ok']) {
            return [
                'answer' => $fallbackAnswer,
                'suggested_next_steps' => $this->fallbackChatSteps($verdict, $responseLanguage),
                'gemini' => [
                    'model' => $runtime['model'],
                    'prompt_version' => self::PROMPT_VERSION,
                    'parsed_ok' => false,
                    'fallback_used' => true,
                    'validation_errors' => [$result['error']],
                    'target_language' => $responseLanguage,
                    'raw_response' => $result['raw_response'],
                ],
            ];
        }

        $decoded = $this->decodeJsonStrict((string) $result['text']);
        if (! is_array($decoded)) {
            return [
                'answer' => $fallbackAnswer,
                'suggested_next_steps' => $this->fallbackChatSteps($verdict, $responseLanguage),
                'gemini' => [
                    'model' => $runtime['model'],
                    'prompt_version' => self::PROMPT_VERSION,
                    'parsed_ok' => false,
                    'fallback_used' => true,
                    'validation_errors' => ['Chat explainer returned non-JSON or invalid JSON content.'],
                    'target_language' => $responseLanguage,
                    'raw_response' => $result['raw_response'],
                ],
            ];
        }

        $answer = $this->extractChatAnswer($decoded);
        $steps = $this->extractChatSteps($decoded);
        $validationErrors = $this->validateChatResponse($decoded, $answer);
        if ($validationErrors !== []) {
            return [
                'answer' => $fallbackAnswer,
                'suggested_next_steps' => $this->fallbackChatSteps($verdict, $responseLanguage),
                'gemini' => [
                    'model' => $runtime['model'],
                    'prompt_version' => self::PROMPT_VERSION,
                    'parsed_ok' => false,
                    'fallback_used' => true,
                    'validation_errors' => $validationErrors,
                    'target_language' => $responseLanguage,
                    'raw_response' => $result['raw_response'],
                ],
            ];
        }

        if ($answer === '') {
            $answer = $fallbackAnswer;
        }

        if ($steps === []) {
            $steps = $this->fallbackChatSteps($verdict, $responseLanguage);
        }

        return [
            'answer' => $answer,
            'suggested_next_steps' => $steps,
            'gemini' => [
                'model' => $runtime['model'],
                'prompt_version' => self::PROMPT_VERSION,
                'parsed_ok' => true,
                'fallback_used' => false,
                'validation_errors' => [],
                'target_language' => $responseLanguage,
                'raw_response' => $result['raw_response'],
                'conversation_turns' => count($conversationHistory),
            ],
        ];
    }

    /**
     * @return array{
     *   api_key: string,
     *   model: string,
     *   base_url: string,
     *   timeout: int,
     *   retries: int,
     *   temperature: float
     * }
     */
    private function runtimeConfig(): array
    {
        return [
            'api_key' => trim((string) config('services.gemini.api_key')),
            'model' => (string) config('services.gemini.model', 'gemini-2.0-flash'),
            'base_url' => rtrim((string) config('services.gemini.base_url', 'https://generativelanguage.googleapis.com'), '/'),
            'timeout' => (int) config('services.gemini.timeout_seconds', 15),
            'retries' => max(0, (int) config('services.gemini.retries', 1)),
            'temperature' => (float) config('services.gemini.temperature', 0.1),
        ];
    }

    /**
     * @return array{
     *   called: bool,
     *   parsed_ok: bool,
     *   items: array<int, array{issue: string, severity: string}>,
     *   summary: string,
     *   validation_errors: array<int, string>,
     *   raw_response: ?string
     * }
     */
    private function detectContradictions(array $payload, array $runtime, string $responseLanguage): array
    {
        $enabled = (bool) config('services.gemini.enable_contradiction_check', true);

        if (! $enabled) {
            return [
                'called' => false,
                'parsed_ok' => false,
                'items' => [],
                'summary' => '',
                'validation_errors' => [],
                'raw_response' => null,
            ];
        }

        $prompt = $this->buildContradictionPrompt($payload, $responseLanguage);
        $result = $this->requestModelJson($prompt, $runtime);

        if (! $result['ok']) {
            return [
                'called' => true,
                'parsed_ok' => false,
                'items' => [],
                'summary' => '',
                'validation_errors' => [$result['error']],
                'raw_response' => $result['raw_response'],
            ];
        }

        $decoded = $this->decodeJsonStrict((string) $result['text']);
        if (! is_array($decoded)) {
            return [
                'called' => true,
                'parsed_ok' => false,
                'items' => [],
                'summary' => '',
                'validation_errors' => ['Contradiction check returned invalid JSON.'],
                'raw_response' => $result['raw_response'],
            ];
        }

        $items = $this->normalizeContradictions($decoded['contradictions'] ?? []);
        $summary = trim((string) ($decoded['summary'] ?? ''));
        $validationErrors = [];

        if (! array_key_exists('contradictions', $decoded)) {
            $validationErrors[] = 'Contradiction JSON missing contradictions key.';
        }

        return [
            'called' => true,
            'parsed_ok' => true,
            'items' => $items,
            'summary' => $summary,
            'validation_errors' => $validationErrors,
            'raw_response' => $result['raw_response'],
        ];
    }

    private function buildChatPrompt(
        string $question,
        array $conversationHistory,
        string $verdict,
        string $verdictLabel,
        int $riskScore,
        array $reasons,
        string $recommendation,
        array $verificationContext,
        array $riskEngine,
        string $plotReference,
        string $responseLanguage
    ): string {
        $languageLabel = $responseLanguage === self::LANG_SW ? 'Kiswahili' : 'English';

        return <<<PROMPT
You are a Tanzanian land verification assistant helping a buyer understand a verification outcome.

Return strict JSON only with exactly:
{
  "answer": "string",
  "suggested_next_steps": ["string"]
}

Rules:
1) Use only provided facts.
2) Write in {$languageLabel} only.
3) Answer buyer_question directly and use conversation_history to avoid repeating the same explanation.
4) Give practical guidance for each relevant risk reason by covering:
   - what that term means in simple language,
   - likely impact if ignored (legal/financial/ownership consequences),
   - what records or checks the buyer should request.
5) Include where the buyer can seek help in Tanzania when relevant (District/Municipal Land Office, Ministry/Ardhi records desk, land tribunal, or licensed advocate).
6) If verdict is CAUTION, explain what "Buy with caution" means in practice.
7) If verdict is DO_NOT_BUY, explain legal and safety implications clearly.
8) Do not change the verdict or risk score.
9) Keep the answer clear and detailed, but not repetitive.

INPUT:
{
  "target_response_language": "{$responseLanguage}",
  "buyer_question": {$this->jsonEncode($question)},
  "conversation_history": {$this->jsonEncode($conversationHistory)},
  "assessment": {
    "verdict": "{$verdict}",
    "verdict_label": "{$verdictLabel}",
    "risk_score": {$riskScore},
    "reasons": {$this->jsonEncode($reasons)},
    "recommendation": {$this->jsonEncode($recommendation)}
  },
  "plot_reference": "{$plotReference}",
  "verification_context": {$this->jsonEncode($verificationContext)},
  "risk_engine": {$this->jsonEncode($riskEngine)}
}
PROMPT;
    }

    /**
     * @return array{
     *   ok: bool,
     *   text: ?string,
     *   raw_response: ?string,
     *   error: string
     * }
     */
    private function requestModelJson(string $prompt, array $runtime): array
    {
        try {
            $request = Http::baseUrl($runtime['base_url'])
                ->withQueryParameters(['key' => $runtime['api_key']])
                ->timeout($runtime['timeout'])
                ->acceptJson();

            if ($runtime['retries'] > 0) {
                $request = $request->retry($runtime['retries'], 300);
            }

            $response = $request->post("/v1beta/models/{$runtime['model']}:generateContent", [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            ['text' => $prompt],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => $runtime['temperature'],
                    'responseMimeType' => 'application/json',
                ],
            ]);

            $rawResponse = Str::limit($response->body(), 5000, '...');

            if (! $response->successful()) {
                return [
                    'ok' => false,
                    'text' => null,
                    'raw_response' => $rawResponse,
                    'error' => sprintf('Gemini request failed with HTTP %d.', $response->status()),
                ];
            }

            $text = $this->extractModelText($response->json());

            if ($text === null) {
                return [
                    'ok' => false,
                    'text' => null,
                    'raw_response' => $rawResponse,
                    'error' => 'Gemini response did not contain a text payload.',
                ];
            }

            return [
                'ok' => true,
                'text' => $text,
                'raw_response' => $rawResponse,
                'error' => '',
            ];
        } catch (Throwable $exception) {
            return [
                'ok' => false,
                'text' => null,
                'raw_response' => null,
                'error' => 'Gemini request exception: '.$exception->getMessage(),
            ];
        }
    }

    private function buildPrompt(
        array $payload,
        string $responseLanguage,
        array $contradictions,
        string $contradictionSummary
    ): string {
        $systemRiskScore = (int) ($payload['system_risk_score'] ?? 0);
        $systemVerdict = (string) ($payload['system_verdict'] ?? 'INCOMPLETE');
        $riskBreakdown = $payload['risk_breakdown'] ?? [];
        $landData = $payload['land_data'] ?? [];
        $verificationContext = $payload['verification_context'] ?? [];
        $languageLabel = $responseLanguage === self::LANG_SW ? 'Kiswahili' : 'English';

        return <<<PROMPT
You are a Tanzanian land verification expert advising property buyers.

Follow these mandatory rules:
1) Do not invent facts that are not in the provided data.
2) Respect the system-calculated risk score and verdict.
3) Return strict JSON only with exactly these keys:
   verdict, risk_score, reasons, recommendation
4) verdict must be one of SAFE, CAUTION, DO_NOT_BUY.
5) risk_score must exactly equal the provided system_risk_score.
6) reasons must be concise, factual, and buyer-responsible.
7) reasons and recommendation must be written in {$languageLabel}.
8) Do not mix languages.
9) Keep verdict enum values in uppercase English exactly as specified.
10) If verdict is CAUTION, recommendation must start with "Buy with caution." (or the equivalent in Kiswahili).
11) If verdict is DO_NOT_BUY, recommendation must start with "Do not buy." (or the equivalent in Kiswahili).

SYSTEM INPUT:
{
  "target_response_language": "{$responseLanguage}",
  "system_risk_score": {$systemRiskScore},
  "system_verdict": "{$systemVerdict}",
  "risk_breakdown": {$this->jsonEncode($riskBreakdown)},
  "verification_context": {$this->jsonEncode($verificationContext)},
  "land_data": {$this->jsonEncode($landData)},
  "ai_contradiction_findings": {$this->jsonEncode([
            'summary' => $contradictionSummary,
            'items' => $contradictions,
        ])}
}

Return JSON only.
PROMPT;
    }

    private function buildContradictionPrompt(array $payload, string $responseLanguage): string
    {
        $languageLabel = $responseLanguage === self::LANG_SW ? 'Kiswahili' : 'English';
        $landData = $payload['land_data'] ?? [];
        $riskBreakdown = $payload['risk_breakdown'] ?? [];
        $verificationContext = $payload['verification_context'] ?? [];

        return <<<PROMPT
You are auditing Tanzanian land verification data for contradictions and inconsistencies.

Return strict JSON only with exactly:
{
  "contradictions": [
    {"issue": "string", "severity": "LOW|MEDIUM|HIGH"}
  ],
  "summary": "string"
}

Rules:
1) Use only the provided data.
2) No hallucinations.
3) Write issue and summary in {$languageLabel}.
4) If no contradictions, return an empty contradictions array and explain briefly in summary.

INPUT:
{
  "target_response_language": "{$responseLanguage}",
  "verification_context": {$this->jsonEncode($verificationContext)},
  "risk_breakdown": {$this->jsonEncode($riskBreakdown)},
  "land_data": {$this->jsonEncode($landData)}
}
PROMPT;
    }

    private function validateResponse(array $decoded, string $systemVerdict, int $systemRiskScore): array
    {
        $errors = [];
        $expectedKeys = ['verdict', 'risk_score', 'reasons', 'recommendation'];
        $actualKeys = array_keys($decoded);
        sort($expectedKeys);
        sort($actualKeys);

        if ($actualKeys !== $expectedKeys) {
            $errors[] = 'Gemini response keys do not match the strict JSON contract.';
        }

        $verdict = strtoupper((string) ($decoded['verdict'] ?? ''));
        if (! in_array($verdict, ['SAFE', 'CAUTION', 'DO_NOT_BUY'], true)) {
            $errors[] = 'Gemini verdict is invalid.';
        }

        if (! is_int($decoded['risk_score'] ?? null)) {
            $errors[] = 'Gemini risk_score must be an integer.';
        } elseif ((int) $decoded['risk_score'] !== $systemRiskScore) {
            $errors[] = 'Gemini risk_score does not match deterministic system score.';
        }

        if ($verdict !== $systemVerdict) {
            $errors[] = 'Gemini verdict does not match deterministic system verdict.';
        }

        $reasons = $decoded['reasons'] ?? null;
        if (! is_array($reasons) || $this->normalizeReasons($reasons) === []) {
            $errors[] = 'Gemini reasons must be a non-empty array of strings.';
        }

        $recommendation = trim((string) ($decoded['recommendation'] ?? ''));
        if ($recommendation === '') {
            $errors[] = 'Gemini recommendation must be a non-empty string.';
        }

        return $errors;
    }

    private function validateChatResponse(array $decoded, string $answer): array
    {
        $errors = [];

        if ($answer === '') {
            $errors[] = 'Chat explainer answer must be non-empty.';
        }

        return $errors;
    }

    private function extractModelText(array $responseData): ?string
    {
        $candidate = $responseData['candidates'][0] ?? null;
        if (! is_array($candidate)) {
            return null;
        }

        $parts = $candidate['content']['parts'] ?? null;
        if (! is_array($parts) || $parts === []) {
            return null;
        }

        $chunks = [];
        foreach ($parts as $part) {
            if (is_array($part) && isset($part['text']) && is_string($part['text'])) {
                $chunks[] = $part['text'];
            }
        }

        $text = trim(implode("\n", $chunks));

        return $text !== '' ? $text : null;
    }

    private function decodeJsonStrict(string $content): ?array
    {
        $decoded = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{[\s\S]*\}/', $content, $matches) !== 1) {
            return null;
        }

        $decoded = json_decode($matches[0], true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        return null;
    }

    private function normalizeReasons(mixed $reasons): array
    {
        if (! is_array($reasons)) {
            return [];
        }

        $normalized = [];

        foreach ($reasons as $reason) {
            $line = trim((string) $reason);
            if ($line !== '') {
                $normalized[] = $line;
            }
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

    private function extractChatAnswer(array $decoded): string
    {
        $candidateKeys = ['answer', 'explanation', 'response', 'message'];

        foreach ($candidateKeys as $key) {
            if (! array_key_exists($key, $decoded)) {
                continue;
            }

            $rawValue = $decoded[$key];
            if (! is_string($rawValue) && ! is_numeric($rawValue) && ! is_bool($rawValue)) {
                continue;
            }

            $value = trim((string) $rawValue);
            if ($value !== '') {
                return $value;
            }
        }

        $nested = $decoded['data'] ?? null;
        if (is_array($nested)) {
            return $this->extractChatAnswer($nested);
        }

        return '';
    }

    private function extractChatSteps(array $decoded): array
    {
        foreach (['suggested_next_steps', 'next_steps', 'actions', 'recommended_steps'] as $key) {
            if (! array_key_exists($key, $decoded)) {
                continue;
            }

            $steps = $this->normalizeSteps($decoded[$key]);
            if ($steps !== []) {
                return $steps;
            }
        }

        $nested = $decoded['data'] ?? null;
        if (is_array($nested)) {
            return $this->extractChatSteps($nested);
        }

        return [];
    }

    private function normalizeSteps(mixed $steps): array
    {
        if (is_string($steps)) {
            $steps = preg_split('/\r\n|\r|\n|;/', $steps) ?: [];
        }

        if (! is_array($steps)) {
            return [];
        }

        $normalized = [];
        foreach ($steps as $step) {
            $line = '';
            if (is_array($step)) {
                $line = trim((string) ($step['step'] ?? $step['text'] ?? ''));
            } else {
                $line = trim((string) $step);
            }

            if ($line !== '') {
                $normalized[] = $line;
            }
        }

        return array_values(array_unique($normalized));
    }

    /**
     * @param  mixed  $items
     * @return array<int, array{issue: string, severity: string}>
     */
    private function normalizeContradictions(mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }

        $normalized = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $issue = trim((string) ($item['issue'] ?? ''));
            if ($issue === '') {
                continue;
            }

            $severity = strtoupper(trim((string) ($item['severity'] ?? 'MEDIUM')));
            if (! in_array($severity, ['LOW', 'MEDIUM', 'HIGH'], true)) {
                $severity = 'MEDIUM';
            }

            $normalized[] = [
                'issue' => $issue,
                'severity' => $severity,
            ];
        }

        return $normalized;
    }

    /**
     * @param  array<int, array{issue: string, severity: string}>  $items
     * @return array<int, string>
     */
    private function contradictionReasons(array $items): array
    {
        $lines = [];

        foreach ($items as $item) {
            $issue = trim((string) ($item['issue'] ?? ''));
            if ($issue !== '') {
                $lines[] = $issue;
            }
        }

        return array_values(array_unique($lines));
    }

    private function fallback(
        string $systemVerdict,
        int $systemRiskScore,
        array $fallbackReasons,
        array $validationErrors,
        ?string $rawResponse = null,
        bool $parsedOk = false,
        ?string $model = null,
        string $responseLanguage = self::LANG_EN,
        array $contradictionCheck = []
    ): array {
        $reasons = $fallbackReasons !== []
            ? $fallbackReasons
            : [$this->defaultFallbackReason($responseLanguage)];

        return [
            'verdict' => $systemVerdict,
            'risk_score' => $systemRiskScore,
            'reasons' => $reasons,
            'recommendation' => $this->fallbackRecommendation($systemVerdict, $responseLanguage),
            'gemini' => [
                'model' => $model ?? (string) config('services.gemini.model', 'gemini-2.0-flash'),
                'prompt_version' => self::PROMPT_VERSION,
                'parsed_ok' => $parsedOk,
                'fallback_used' => true,
                'validation_errors' => $validationErrors,
                'target_language' => $responseLanguage,
                'raw_response' => $rawResponse,
                'contradiction_check' => $this->compactContradictionMeta($contradictionCheck),
            ],
        ];
    }

    private function compactContradictionMeta(array $meta): array
    {
        if ($meta === []) {
            return [
                'called' => false,
                'parsed_ok' => false,
                'items_count' => 0,
                'validation_errors' => [],
                'raw_response' => null,
            ];
        }

        return [
            'called' => (bool) ($meta['called'] ?? false),
            'parsed_ok' => (bool) ($meta['parsed_ok'] ?? false),
            'items_count' => count($meta['items'] ?? []),
            'summary' => (string) ($meta['summary'] ?? ''),
            'validation_errors' => array_values($meta['validation_errors'] ?? []),
            'raw_response' => $meta['raw_response'] ?? null,
        ];
    }

    private function fallbackRecommendation(string $verdict, string $responseLanguage): string
    {
        if ($responseLanguage === self::LANG_SW) {
            return match ($verdict) {
                'SAFE' => 'Salama kununua. Endelea na ukaguzi wa kawaida wa nyaraka na uhakiki wa uhamisho katika ofisi husika ya ardhi.',
                'CAUTION' => 'Nunua kwa tahadhari. Endelea baada ya kutatua hoja zote zilizobainishwa na kupata kumbukumbu rasmi kutoka mamlaka za ardhi na sheria.',
                'DO_NOT_BUY' => 'Usinunue. Usiendelee na ununuzi huu hadi hatari za kisheria na umiliki ziondolewe rasmi na mamlaka husika.',
                default => 'Uthibitishaji haujakamilika. Rudia hatua za uthibitishaji na hakiki kumbukumbu kabla ya kuendelea.',
            };
        }

        return match ($verdict) {
            'SAFE' => 'Safe to buy. Proceed with standard due diligence and verify transfer documents at the relevant land office.',
            'CAUTION' => 'Buy with caution. Proceed only after resolving all flagged issues and obtaining certified records from land and legal authorities.',
            'DO_NOT_BUY' => 'Do not buy. Do not proceed with this purchase until the legal and ownership risks are formally cleared by the competent authorities.',
            default => 'Verification is incomplete. Re-run verification steps and confirm records before proceeding.',
        };
    }

    private function normalizeRecommendationByVerdict(string $recommendation, string $verdict, string $responseLanguage): string
    {
        if ($recommendation === '') {
            return $this->fallbackRecommendation($verdict, $responseLanguage);
        }

        $prefix = $this->recommendationPrefix($verdict, $responseLanguage);

        if ($prefix === '') {
            return $recommendation;
        }

        if (Str::startsWith(Str::lower($recommendation), Str::lower($prefix))) {
            return $recommendation;
        }

        return trim($prefix.' '.$recommendation);
    }

    private function buildChatFallbackAnswer(
        string $verdict,
        string $verdictLabel,
        int $riskScore,
        array $reasons,
        string $recommendation,
        string $responseLanguage
    ): string {
        $expandedReasons = [];
        foreach (array_slice($reasons, 0, 4) as $reason) {
            $expandedReasons[] = $this->expandFallbackReason((string) $reason, $responseLanguage);
        }

        if ($expandedReasons === []) {
            $expandedReasons[] = $responseLanguage === self::LANG_SW
                ? 'Hakuna sababu za ziada zilizowasilishwa kwenye matokeo haya.'
                : 'No additional risk reasons were provided in this result.';
        }

        if ($responseLanguage === self::LANG_SW) {
            $lines = [
                sprintf(
                    'Tathmini hii ni %s (alama ya hatari %d/100).',
                    $verdictLabel !== '' ? $verdictLabel : $verdict,
                    $riskScore
                ),
                'Maana yake kwa mnunuzi: usifanye malipo ya mwisho kabla ya kuhakiki hoja zote za umiliki na kisheria.',
                'Sababu muhimu na athari zake:',
            ];

            foreach ($expandedReasons as $reasonLine) {
                $lines[] = '- '.$reasonLine;
            }

            $lines[] = 'Mahali pa kupata msaada: Ofisi ya Ardhi ya Halmashauri/Wilaya, dawati la kumbukumbu za ardhi, au mwanasheria mwenye leseni.';
            $lines[] = 'Mapendekezo: '.$recommendation;

            return implode("\n", $lines);
        }

        $lines = [
            sprintf(
                'This assessment is %s (risk score %d/100).',
                $verdictLabel !== '' ? $verdictLabel : $verdict,
                $riskScore
            ),
            'What this means for a buyer: avoid final payment until ownership and legal risks are fully verified.',
            'Key reasons and likely impact:',
        ];

        foreach ($expandedReasons as $reasonLine) {
            $lines[] = '- '.$reasonLine;
        }

        $lines[] = 'Where to seek help: District/Municipal Land Office, land records desk, or a licensed property advocate.';
        $lines[] = 'Recommendation: '.$recommendation;

        return implode("\n", $lines);
    }

    private function expandFallbackReason(string $reason, string $responseLanguage): string
    {
        $line = trim($reason);
        if ($line === '') {
            return '';
        }

        $lower = Str::lower($line);

        if (Str::contains($lower, ['caveat', 'zuio'])) {
            return $responseLanguage === self::LANG_SW
                ? $line.' Hii huonyesha madai/zuio la kisheria juu ya kiwanja; linaweza kusimamisha uhamisho hadi liwe limeondolewa rasmi.'
                : $line.' This indicates a legal restriction/claim on the land and transfer can be blocked until formally removed.';
        }

        if (Str::contains($lower, ['dispute', 'mgogoro'])) {
            return $responseLanguage === self::LANG_SW
                ? $line.' Mgogoro wa kisheria unaweza kuchelewesha au kubatilisha ununuzi, hivyo hakiki kumbukumbu za mahakama/tribunal kabla ya mkataba.'
                : $line.' An active legal dispute can delay or void transfer, so verify court/tribunal status before contracting.';
        }

        if (Str::contains($lower, ['loan', 'encumbrance', 'dhamana', 'mkopo'])) {
            return $responseLanguage === self::LANG_SW
                ? $line.' Dhamana ya benki inaweza kuipa benki haki ya madai juu ya mali; pata kibali cha kuondoa mzigo huo kabla ya malipo.'
                : $line.' A bank encumbrance may give a lender rights over the property; request formal discharge evidence before payment.';
        }

        if (Str::contains($lower, ['double allocation', 'ugawaji wa mara mbili'])) {
            return $responseLanguage === self::LANG_SW
                ? $line.' Ugawaji wa mara mbili una hatari kubwa ya mgogoro wa umiliki; hakikisha kumbukumbu rasmi za umiliki zinalingana.'
                : $line.' Double allocation carries high ownership conflict risk; confirm official title records before proceeding.';
        }

        if (Str::contains($lower, ['ownership changed', 'umiliki umebadilika'])) {
            return $responseLanguage === self::LANG_SW
                ? $line.' Mabadiliko mengi ya umiliki huongeza hatari ya mapungufu kwenye nyaraka; hakiki mlolongo wa uhamisho mmoja baada ya mwingine.'
                : $line.' Frequent transfers increase chain-of-title risk; verify each transfer document sequentially.';
        }

        if (Str::contains($lower, ['land rate', 'kodi ya ardhi'])) {
            return $responseLanguage === self::LANG_SW
                ? $line.' Madeni ya kodi ya ardhi yanaweza kuzuia uhamisho; omba hati rasmi ya malipo kutoka mamlaka husika.'
                : $line.' Land rate arrears can block transfer; request official clearance from the relevant authority.';
        }

        if (Str::contains($lower, ['certificate', 'cheti'])) {
            return $responseLanguage === self::LANG_SW
                ? $line.' Tatizo la cheti linaweza kufanya umiliki usiwe salama; hakiki uhalali wa cheti katika ofisi ya ardhi.'
                : $line.' Certificate issues can weaken title security; verify certificate validity at the land office.';
        }

        return $line;
    }

    private function fallbackChatSteps(string $verdict, string $responseLanguage): array
    {
        if ($responseLanguage === self::LANG_SW) {
            return match ($verdict) {
                'SAFE' => [
                    'Hakiki nyaraka za uhamisho katika ofisi ya ardhi kabla ya malipo ya mwisho.',
                    'Weka kumbukumbu za malipo na mikataba yote kwa maandishi.',
                ],
                'CAUTION' => [
                    'Tatua kwanza hoja zote zilizoorodheshwa kabla ya kuendelea na ununuzi.',
                    'Pata uthibitisho rasmi wa maandishi kutoka mamlaka husika.',
                ],
                'DO_NOT_BUY' => [
                    'Usiingie mkataba wa ununuzi hadi hatari za umiliki/kisheria ziondolewe rasmi.',
                    'Wasiliana na ofisi ya ardhi au mshauri wa sheria kwa uhakiki wa kina.',
                ],
                default => [
                    'Rudia hatua za uthibitishaji ili kupata taarifa kamili.',
                ],
            };
        }

        return match ($verdict) {
            'SAFE' => [
                'Confirm transfer documents at the land office before final payment.',
                'Keep written records of all agreements and payments.',
            ],
            'CAUTION' => [
                'Resolve all flagged issues before committing to purchase.',
                'Obtain certified written confirmation from relevant authorities.',
            ],
            'DO_NOT_BUY' => [
                'Do not enter a purchase contract until ownership/legal risks are formally cleared.',
                'Consult the land office or legal advisor for deeper verification.',
            ],
            default => [
                'Repeat verification steps to gather complete information.',
            ],
        };
    }

    private function recommendationPrefix(string $verdict, string $responseLanguage): string
    {
        if ($responseLanguage === self::LANG_SW) {
            return match ($verdict) {
                'SAFE' => 'Salama kununua.',
                'CAUTION' => 'Nunua kwa tahadhari.',
                'DO_NOT_BUY' => 'Usinunue.',
                default => '',
            };
        }

        return match ($verdict) {
            'SAFE' => 'Safe to buy.',
            'CAUTION' => 'Buy with caution.',
            'DO_NOT_BUY' => 'Do not buy.',
            default => '',
        };
    }

    private function defaultFallbackReason(string $responseLanguage): string
    {
        if ($responseLanguage === self::LANG_SW) {
            return 'Tathmini imetumia kanuni za hatari zilizowekwa na mfumo kwa sababu majibu ya AI hayakupatikana.';
        }

        return 'Assessment used deterministic risk rules because AI output was unavailable.';
    }

    private function jsonEncode(mixed $payload): string
    {
        return (string) json_encode(
            $payload,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
    }

    private function normalizeLanguage(string $language): string
    {
        $language = strtolower(trim($language));

        if ($language === self::LANG_SW) {
            return self::LANG_SW;
        }

        return self::LANG_EN;
    }
}
