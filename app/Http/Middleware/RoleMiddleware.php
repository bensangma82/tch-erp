<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(
        Request $request,
        Closure $next,
        string ...$roles
    ): Response {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        /*
        |--------------------------------------------------------------------------
        | Administrator Override
        |--------------------------------------------------------------------------
        |
        | Admin users can access all protected ERP modules.
        |
        */

        if ($user->isAdmin()) {
            return $next($request);
        }

        /*
        |--------------------------------------------------------------------------
        | Role Check
        |--------------------------------------------------------------------------
        */

        if (! $user->hasAnyRole($roles)) {
            abort(403, 'You do not have permission to access this module.');
        }

        return $next($request);
    }
}