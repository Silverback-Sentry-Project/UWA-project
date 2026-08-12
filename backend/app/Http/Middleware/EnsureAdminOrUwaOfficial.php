<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminOrUwaOfficial
{
    /**
     * Narrower than the 'admin' alias (which also admits Park Warden and
     * Gamepark Officer). Compensation claims and forwarded-form review are
     * cross-park, HQ-level data with no per-park scoping in their queries or
     * UI, so they stay limited to System Administrator and UWA Official —
     * the two roles not tied to a single park.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! ($user->isAdmin() || $user->hasRole('UWA Official'))) {
            return response()->json([
                'message' => 'Forbidden. This is restricted to System Administrators and UWA Officials.',
            ], 403);
        }

        return $next($request);
    }
}
