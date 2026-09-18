<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(
        Request $request,
        Closure $next,
        string ...$permissions
    ): Response {

        $user = $request->user();


        if (! $user) {
            abort(401);
        }


        /*
        |--------------------------------------------------------------------------
        | Admin override
        |--------------------------------------------------------------------------
        */

        if ($user->isAdmin()) {
            return $next($request);
        }


        /*
        |--------------------------------------------------------------------------
        | Permission check
        |--------------------------------------------------------------------------
        */

        if (! $user->hasAnyPermission($permissions)) {
            abort(
                403,
                'You do not have permission to perform this action.'
            );
        }


        return $next($request);
    }
}