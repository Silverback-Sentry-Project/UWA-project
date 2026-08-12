<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            ! $user
            || ! (
                $user->isAdmin()
                || $user->hasRole('UWA Official')
                || $user->hasRole('Park Warden')
                || $user->hasRole('Gamepark Officer')
            )
        ) {
            return response()->json([
                'message' => 'Forbidden. This portal is restricted to authorized personnel.',
            ], 403);
        }

        return $next($request);
    }
}
