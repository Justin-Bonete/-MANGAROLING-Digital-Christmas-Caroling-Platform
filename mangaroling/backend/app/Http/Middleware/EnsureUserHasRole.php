<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Usage: ->middleware('role:system_admin,finance_officer'). Prefer 'can:<permission>' for feature checks. */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        abort_unless($request->user()?->hasRole(...$roles), 403, 'You do not have the required role.');

        return $next($request);
    }
}
