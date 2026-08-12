<?php

namespace App\Http\Middleware;

use App\Services\FirebaseService;
use Closure;
use Illuminate\Http\Request;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticates mobile-app-direct calls to the bridge endpoints (incidents/sightings/sos-alerts)
 * using the caller's Firebase ID token, instead of the HMAC scheme the old Cloud-Functions-relayed
 * webhooks used. Deliberately does not require a matching Laravel `users` row to exist - most
 * mobile reporters (including anonymous guests, who still get a valid Firebase ID token) have no
 * portal-side account at all; FirestoreSyncMapper::resolveUserId() already handles an unmatched
 * firebase_uid gracefully (falls back to a placeholder reporter) and that behavior is unchanged
 * here, just no longer gated behind a Cloud Function that verified nothing about the caller beyond
 * "Firestore accepted this write."
 *
 * A valid, unexpired, non-revoked Firebase ID token is proof the request comes from someone the
 * app's own sign-in flow (including anonymous/guest auth) actually authenticated - the same trust
 * level Firestore's own security rules already extend to a client write. No additional role check
 * is applied here.
 */
class VerifyFirebaseIdToken
{
    public function __construct(private readonly FirebaseService $firebase)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->header('Authorization');
        if (! is_string($header) || ! str_starts_with($header, 'Bearer ')) {
            return response()->json(['message' => 'Missing Firebase ID token.'], 401);
        }

        $idToken = substr($header, 7);

        try {
            $verified = $this->firebase->auth()->verifyIdToken($idToken);
        } catch (FailedToVerifyToken $e) {
            return response()->json(['message' => 'Invalid or expired Firebase ID token.'], 401);
        }

        $uid = $verified->claims()->get('sub');
        if (! is_string($uid) || $uid === '') {
            return response()->json(['message' => 'Invalid Firebase ID token.'], 401);
        }

        $request->attributes->set('firebase_uid', $uid);

        return $next($request);
    }
}
