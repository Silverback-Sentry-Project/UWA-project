<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureGamepark
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isGamepark()) {
            return response()->json([
                'message' => 'Forbidden. This portal is restricted to Gamepark accounts.',
            ], 403);
        }

        return $next($request);
    }
}
