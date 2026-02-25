<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class SetApiLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolveLocale($request);

        app()->setLocale($locale);

        /** @var Response $response */
        $response = $next($request);
        $response->headers->set('Content-Language', $locale);

        return $response;
    }

    private function resolveLocale(Request $request): string
    {
        $supportedLocales = $this->supportedLocales();
        $defaultLocale = $this->normalizeLocale((string) config('app.locale', 'en'));

        foreach ([
            $request->query('locale'),
            $request->query('lang'),
            $request->header('X-Locale'),
        ] as $candidate) {
            if (is_string($candidate)) {
                $locale = $this->normalizeLocale($candidate);

                if (in_array($locale, $supportedLocales, true)) {
                    return $locale;
                }
            }
        }

        foreach ($this->parseAcceptLanguage((string) $request->header('Accept-Language', '')) as $candidate) {
            $locale = $this->normalizeLocale($candidate);

            if (in_array($locale, $supportedLocales, true)) {
                return $locale;
            }
        }

        if (in_array($defaultLocale, $supportedLocales, true)) {
            return $defaultLocale;
        }

        return $supportedLocales[0] ?? 'en';
    }

    /**
     * @return list<string>
     */
    private function supportedLocales(): array
    {
        $configured = config('app.supported_locales', ['en']);
        $locales = is_array($configured) ? $configured : explode(',', (string) $configured);
        $normalized = array_map(
            fn (mixed $locale): string => $this->normalizeLocale((string) $locale),
            $locales
        );
        $normalized = array_values(array_filter($normalized, static fn (string $locale): bool => $locale !== ''));

        if ($normalized === []) {
            return ['en'];
        }

        return array_values(array_unique($normalized));
    }

    /**
     * @return list<string>
     */
    private function parseAcceptLanguage(string $header): array
    {
        if ($header === '') {
            return [];
        }

        $parsed = [];

        foreach (explode(',', $header) as $item) {
            $item = trim($item);

            if ($item === '') {
                continue;
            }

            $parts = explode(';', $item);
            $language = trim($parts[0] ?? '');
            $quality = 1.0;

            foreach (array_slice($parts, 1) as $parameter) {
                $parameter = trim($parameter);

                if (! str_starts_with($parameter, 'q=')) {
                    continue;
                }

                $qValue = (float) substr($parameter, 2);

                if ($qValue >= 0 && $qValue <= 1) {
                    $quality = $qValue;
                }
            }

            if ($language !== '') {
                $parsed[] = [
                    'language' => $language,
                    'quality' => $quality,
                ];
            }
        }

        usort($parsed, static fn (array $a, array $b): int => $b['quality'] <=> $a['quality']);

        return array_values(array_map(static fn (array $item): string => $item['language'], $parsed));
    }

    private function normalizeLocale(string $locale): string
    {
        $normalized = Str::of($locale)
            ->replace('_', '-')
            ->lower()
            ->trim()
            ->value();

        if ($normalized === '') {
            return '';
        }

        return explode('-', $normalized)[0];
    }
}
