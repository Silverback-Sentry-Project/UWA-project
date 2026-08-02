<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWardenOrUwaOfficial
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            ! $user
            || ! (
                $user->hasRole('Park Warden')
                || $user->hasRole('UWA Official')
            )
        ) {
            return response()->json([
                'message' => 'Forbidden. News articles are restricted to Park Wardens and UWA Officials.',
            ], 403);
        }

        return $next($request);
    }
}
