<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Renders every API error as RFC 9457 Problem Details (application/problem+json).
 *
 * Plain HTTP errors use the "about:blank" type, whose title is the HTTP status text.
 * Validation errors keep Laravel's "errors" member, so forms read them as before.
 */
final class ProblemDetailsRenderer
{
    public const VALIDATION_TYPE = '/problems/validation-error';

    public function render(Throwable $e, Request $request): ?JsonResponse
    {
        // Responses built on purpose, and non-API requests, keep Laravel's rendering.
        if ($e instanceof HttpResponseException || ! $request->is('api/*')) {
            return null;
        }

        if ($e instanceof ValidationException) {
            return $this->respond($request, $e->status, [
                'type' => self::VALIDATION_TYPE,
                'title' => 'The given data was invalid.',
                'detail' => $e->getMessage(),
                'errors' => $e->errors(),
            ]);
        }

        if ($e instanceof AuthenticationException) {
            return $this->respond($request, 401, ['detail' => 'Authentication is required.']);
        }

        // Laravel has already turned model-not-found and authorization errors into HTTP exceptions.
        if ($e instanceof HttpExceptionInterface) {
            return $this->respond($request, $e->getStatusCode(), ['detail' => $this->httpDetail($e)], $e->getHeaders());
        }

        return $this->respond($request, 500, $this->debug($e));
    }

    /**
     * @param  array<string, mixed>  $members
     * @param  array<string, string>  $headers
     */
    private function respond(Request $request, int $status, array $members, array $headers = []): JsonResponse
    {
        $problem = [
            'type' => $members['type'] ?? 'about:blank',
            'title' => $members['title'] ?? (Response::$statusTexts[$status] ?? 'Error'),
            'status' => $status,
            'detail' => $members['detail'] ?? null,
            'instance' => '/'.$request->path(),
        ] + $members;

        return new JsonResponse(
            array_filter($problem, fn (mixed $value): bool => $value !== null),
            $status,
            ['Content-Type' => 'application/problem+json'] + $headers,
        );
    }

    private function httpDetail(HttpExceptionInterface $e): ?string
    {
        // The model-not-found message names the PHP class: never send it to clients.
        if ($e->getStatusCode() === 404) {
            return 'The requested resource was not found.';
        }

        return $e->getMessage() !== '' ? $e->getMessage() : null;
    }

    /**
     * Exception details for local debugging only: never with APP_DEBUG=false.
     *
     * @return array<string, mixed>
     */
    private function debug(Throwable $e): array
    {
        if (! config('app.debug')) {
            return [];
        }

        return ['debug' => [
            'exception' => $e::class,
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]];
    }
}
