<?php

use App\Exceptions\ProblemDetailsRenderer;
use App\Exceptions\ProblemException;
use App\Http\Middleware\SetCurrentCompany;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Sanctum SPA: requests from the stateful domains (config/sanctum.php) get cookies, session and
        // CSRF protection, and authenticate with the session instead of a token.
        $middleware->statefulApi();

        // The tenant comes from the authenticated user. The priority list runs this middleware after
        // authentication (already earlier in the list) and before route model binding.
        $middleware->api(append: [SetCurrentCompany::class]);
        $middleware->prependToPriorityList(before: SubstituteBindings::class, prepend: SetCurrentCompany::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Domain problems are expected client errors (like 4xx HTTP exceptions): not worth an error log.
        $exceptions->dontReport(ProblemException::class);

        $exceptions->render(
            fn (Throwable $e, Request $request) => (new ProblemDetailsRenderer)->render($e, $request),
        );
    })->create();
