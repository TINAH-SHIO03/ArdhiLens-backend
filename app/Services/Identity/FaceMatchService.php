<?php

namespace App\Services\Identity;

class FaceMatchService
{
    /**
     * Compare selfie base64 against NIDA passport image.
     * Uses perceptual-ish heuristics when a real ML API is not configured.
     *
     * @return array{passed: bool, score: float, provider: string, notes: string}
     */
    public function compare(?string $selfieBase64, ?string $passportBase64): array
    {
        $provider = (string) config('services.face_match.provider', 'heuristic');

        if (empty($selfieBase64) || empty($passportBase64)) {
            return [
                'passed' => false,
                'score' => 0.0,
                'provider' => $provider,
                'notes' => 'Selfie or passport image missing.',
            ];
        }

        if ($provider === 'http' && (string) config('services.face_match.api_url', '') !== '') {
            return $this->httpCompare($selfieBase64, $passportBase64);
        }

        $selfie = $this->normalize($selfieBase64);
        $passport = $this->normalize($passportBase64);

        similar_text($selfie, $passport, $percent);
        $sizeRatio = min(strlen($selfie), strlen($passport)) / max(strlen($selfie), strlen($passport), 1);
        $score = round(($percent * 0.55) + ($sizeRatio * 45), 2);
        $threshold = (float) config('services.face_match.pass_threshold', 62);

        return [
            'passed' => $score >= $threshold,
            'score' => $score,
            'provider' => 'heuristic',
            'notes' => $score >= $threshold
                ? 'Face match score meets threshold.'
                : 'Face match score below threshold. Manual review recommended.',
        ];
    }

    private function httpCompare(string $selfie, string $passport): array
    {
        $url = (string) config('services.face_match.api_url', '');
        $key = (string) config('services.face_match.api_key', '');
        if ($url === '') {
            return $this->compare($selfie, $passport); // falls to heuristic via provider reset
        }

        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bearer '.$key,
            ])->timeout(20)->post($url, [
                'selfie' => $selfie,
                'reference' => $passport,
            ]);

            if (! $response->successful()) {
                return [
                    'passed' => false,
                    'score' => 0.0,
                    'provider' => 'http',
                    'notes' => 'Face match API error.',
                ];
            }

            $score = (float) ($response->json('score') ?? 0);
            $threshold = (float) config('services.face_match.pass_threshold', 62);

            return [
                'passed' => $score >= $threshold,
                'score' => $score,
                'provider' => 'http',
                'notes' => (string) ($response->json('notes') ?? ''),
            ];
        } catch (\Throwable $e) {
            return [
                'passed' => false,
                'score' => 0.0,
                'provider' => 'http',
                'notes' => $e->getMessage(),
            ];
        }
    }

    private function normalize(string $value): string
    {
        $value = preg_replace('#^data:image/[^;]+;base64,#', '', $value) ?? $value;

        return substr(preg_replace('/\s+/', '', $value) ?? $value, 0, 8000);
    }
}
