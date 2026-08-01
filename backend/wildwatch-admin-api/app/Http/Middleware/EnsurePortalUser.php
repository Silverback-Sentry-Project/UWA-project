<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePortalUser
{
    /**
     * Shared gate for both portals. A Gamepark account is not blocked from
     * general data the way "admin-only" routes are — it simply never sees
     * data outside its own park, which each controller enforces by scoping
     * queries to $request->user()->park_id when isGamepark() is true.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || (! $user->isAdmin() && ! $user->isGamepark())) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return $next($request);
    }
}
