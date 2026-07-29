<?php

namespace App\Services\Identity;

use Illuminate\Support\Facades\Http;

class HttpNidaProvider implements NidaProviderInterface
{
    public function __construct(
        private readonly LocalNidaProvider $fallback,
    ) {}

    public function lookup(string $nin): ?array
    {
        $baseUrl = rtrim((string) config('services.nida.base_url', ''), '/');
        $apiKey = (string) config('services.nida.api_key', '');

        if ($baseUrl === '' || $apiKey === '') {
            return $this->fallback->lookup($nin);
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
                'Accept' => 'application/json',
            ])->timeout(12)->get("{$baseUrl}/identity/{$nin}");

            if (! $response->successful()) {
                return $this->fallback->lookup($nin);
            }

            $data = $response->json();
            if (! is_array($data)) {
                return $this->fallback->lookup($nin);
            }

            $data['source'] = 'live';

            return $data;
        } catch (\Throwable) {
            return $this->fallback->lookup($nin);
        }
    }
}
