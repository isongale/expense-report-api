<?php

namespace Tests\Feature;

use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Browser-facing setup for the Nuxt SPA: CORS with credentials and Sanctum stateful requests.
 *
 * CSRF itself cannot be tested here: Laravel skips the token check while running tests.
 */
class SpaAuthenticationSetupTest extends TestCase
{
    private const FRONTEND = 'http://localhost:3000';

    public function test_the_frontend_origin_may_call_the_api_with_credentials(): void
    {
        $this->preflight(self::FRONTEND)
            ->assertNoContent()
            ->assertHeader('Access-Control-Allow-Origin', self::FRONTEND)
            ->assertHeader('Access-Control-Allow-Credentials', 'true');
    }

    public function test_other_origins_are_not_allowed(): void
    {
        // With a single allowed origin, the header always names it (php-cors): the browser compares it
        // with the page origin and blocks the response, because it is not the requesting origin.
        $this->preflight('https://evil.example')
            ->assertHeader('Access-Control-Allow-Origin', self::FRONTEND);
    }

    public function test_requests_from_the_frontend_get_a_session(): void
    {
        $this->withHeaders(['Origin' => self::FRONTEND, 'Referer' => self::FRONTEND.'/'])
            ->getJson('/api/v1/expenses')
            ->assertUnauthorized()
            ->assertCookie(config('session.cookie'));
    }

    public function test_other_clients_stay_stateless(): void
    {
        $this->getJson('/api/v1/expenses')
            ->assertUnauthorized()
            ->assertCookieMissing(config('session.cookie'));
    }

    public function test_the_csrf_cookie_endpoint_sets_the_xsrf_token(): void
    {
        $this->withHeaders(['Origin' => self::FRONTEND])
            ->get('/sanctum/csrf-cookie')
            ->assertNoContent()
            ->assertCookie('XSRF-TOKEN', null, false)
            ->assertHeader('Access-Control-Allow-Origin', self::FRONTEND);
    }

    private function preflight(string $origin): TestResponse
    {
        return $this->call('OPTIONS', '/api/v1/expenses', server: [
            'HTTP_ORIGIN' => $origin,
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
            'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'content-type,x-xsrf-token',
        ]);
    }
}
