<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Handle an incoming request.
     */
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $user = $request->user();

        /*
        |--------------------------------------------------------------------------
        | No Authenticated User
        |--------------------------------------------------------------------------
        |
        | Authentication middleware will handle unauthenticated requests.
        |
        */

        if (! $user) {
            return $next($request);
        }


        /*
        |--------------------------------------------------------------------------
        | Active User
        |--------------------------------------------------------------------------
        */

        if ($user->isActive()) {
            return $next($request);
        }


        /*
        |--------------------------------------------------------------------------
        | Inactive User
        |--------------------------------------------------------------------------
        |
        | Immediately invalidate the current login session.
        |
        */

        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();


        return redirect()
            ->route('login')
            ->withErrors([
                'email' =>
                    'Your account has been deactivated. Please contact the system administrator.',
            ]);
    }
}