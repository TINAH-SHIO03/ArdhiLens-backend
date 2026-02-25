<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiLocaleMiddlewareTest extends TestCase
{
    public function test_it_uses_accept_language_for_api_responses(): void
    {
        $response = $this
            ->withHeaders(['Accept-Language' => 'sw'])
            ->postJson('/api/auth/login', []);

        $response
            ->assertStatus(422)
            ->assertHeader('Content-Language', 'sw')
            ->assertJsonPath('message', 'Uthibitishaji wa taarifa umeshindikana.');
    }

    public function test_it_falls_back_to_default_locale_when_language_is_unsupported(): void
    {
        $response = $this
            ->withHeaders(['Accept-Language' => 'fr'])
            ->postJson('/api/auth/login', []);

        $response
            ->assertStatus(422)
            ->assertHeader('Content-Language', 'en')
            ->assertJsonPath('message', 'Validation failed.');
    }

    public function test_query_locale_takes_priority_over_accept_language(): void
    {
        $response = $this
            ->withHeaders(['Accept-Language' => 'sw'])
            ->postJson('/api/auth/login?locale=en', []);

        $response
            ->assertStatus(422)
            ->assertHeader('Content-Language', 'en')
            ->assertJsonPath('message', 'Validation failed.');
    }
}
