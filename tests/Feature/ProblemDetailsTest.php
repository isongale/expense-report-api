<?php

namespace Tests\Feature;

use App\Exceptions\ProblemDetailsRenderer;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

class ProblemDetailsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Probe routes, registered only for these tests.
        Route::middleware('api')->prefix('api/probe')->group(function () {
            Route::post('validation', fn (Request $request) => $request->validate(['amount' => 'required|integer']));
            Route::get('expenses/{expense}', fn (Expense $expense) => $expense)->middleware('auth:sanctum');
            Route::get('forbidden', fn () => abort(403));
            Route::get('authenticated', fn () => 'ok')->middleware('auth:sanctum');
            Route::get('crash', fn () => throw new RuntimeException('Database password is wrong'));
            // CSRF is skipped while running tests: throw the exception the middleware would throw.
            Route::post('csrf', fn () => throw new TokenMismatchException('CSRF token mismatch.'));
        });
    }

    public function test_validation_errors_keep_the_errors_member(): void
    {
        $this->postJson('/api/probe/validation')
            ->assertStatus(422)
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertExactJson([
                'type' => ProblemDetailsRenderer::VALIDATION_TYPE,
                'title' => 'The given data was invalid.',
                'status' => 422,
                'detail' => 'The amount field is required.',
                'instance' => '/api/probe/validation',
                'errors' => ['amount' => ['The amount field is required.']],
            ]);
    }

    public function test_a_missing_model_does_not_reveal_the_class_name(): void
    {
        $response = $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson('/api/probe/expenses/01a1aaaa-0000-7000-8000-000000000000')
            ->assertNotFound()
            ->assertExactJson([
                'type' => 'about:blank',
                'title' => 'Not Found',
                'status' => 404,
                'detail' => 'The requested resource was not found.',
                'instance' => '/api/probe/expenses/01a1aaaa-0000-7000-8000-000000000000',
            ]);

        $this->assertStringNotContainsString('App\\Models', $response->getContent());
    }

    public function test_an_unknown_route_is_a_problem_too(): void
    {
        $this->getJson('/api/v1/does-not-exist')
            ->assertNotFound()
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('detail', 'The requested resource was not found.');
    }

    public function test_unauthenticated_requests_get_401(): void
    {
        $this->getJson('/api/probe/authenticated')
            ->assertUnauthorized()
            ->assertJsonPath('type', 'about:blank')
            ->assertJsonPath('title', 'Unauthorized')
            ->assertJsonPath('detail', 'Authentication is required.');
    }

    public function test_forbidden_requests_get_403(): void
    {
        $this->getJson('/api/probe/forbidden')
            ->assertForbidden()
            ->assertJsonPath('title', 'Forbidden');
    }

    public function test_http_headers_are_kept(): void
    {
        $this->deleteJson('/api/probe/forbidden')
            ->assertStatus(405)
            ->assertHeader('Allow');
    }

    public function test_an_expired_csrf_token_tells_how_to_recover(): void
    {
        $this->postJson('/api/probe/csrf')
            ->assertStatus(419)
            ->assertJsonPath('title', 'Page Expired')
            ->assertJsonPath('detail', 'The CSRF token is missing or expired: request /sanctum/csrf-cookie again.');
    }

    public function test_server_errors_hide_the_exception_without_debug(): void
    {
        config(['app.debug' => false]);

        $response = $this->getJson('/api/probe/crash')
            ->assertStatus(500)
            ->assertExactJson([
                'type' => 'about:blank',
                'title' => 'Internal Server Error',
                'status' => 500,
                'instance' => '/api/probe/crash',
            ]);

        $this->assertStringNotContainsString('password', $response->getContent());
    }

    public function test_server_errors_show_debug_details_with_debug(): void
    {
        config(['app.debug' => true]);

        $this->getJson('/api/probe/crash')
            ->assertStatus(500)
            ->assertJsonPath('debug.exception', RuntimeException::class);
    }
}
