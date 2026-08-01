<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyWebhookSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('services.firebase_bridge.secret');

        if (empty($secret)) {
            return response()->json(['message' => 'Webhook secret is not configured.'], 503);
        }

        $signature = $request->header('X-WildWatch-Signature');
        if (! is_string($signature) || $signature === '') {
            return response()->json(['message' => 'Missing webhook signature.'], 401);
        }

        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $secret);

        if (! hash_equals($expected, $signature)) {
            return response()->json(['message' => 'Invalid webhook signature.'], 401);
        }

        return $next($request);
    }
}
