<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Accounts with a temporary password may only reach the auth endpoints until they change it. */
class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->must_change_password) {
            return response()->json(
                ['message' => 'You must change your password before continuing.', 'code' => 'password_change_required'],
                403,
            );
        }

        return $next($request);
    }
}
