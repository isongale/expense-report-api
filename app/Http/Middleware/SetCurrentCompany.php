<?php

namespace App\Http\Middleware;

use App\Tenancy\CurrentCompany;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sets the tenant from the authenticated user, never from the request payload.
 *
 * Runs after authentication and before route model binding (priority list in bootstrap/app.php),
 * so bound models are already limited to the user's company.
 */
final class SetCurrentCompany
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($user = $request->user()) {
            CurrentCompany::set($user->company_id);
        }

        return $next($request);
    }
}
