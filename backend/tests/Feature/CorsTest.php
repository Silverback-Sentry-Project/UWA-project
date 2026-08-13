<?php

namespace Tests\Feature;

use Tests\TestCase;

class CorsTest extends TestCase
{
    // Regression test for a live 2026-08-13 bug: the deployed Cloudflare Workers frontend
    // origin got no Access-Control-Allow-Origin header back from the API, blocking login in
    // any real browser (curl doesn't enforce CORS, so this was invisible to a plain HTTP
    // check - only caught by the browser's own console). config/cors.php now hardcodes the
    // known production origin as a floor, independent of the Render dashboard's
    // CORS_ALLOWED_ORIGINS env var actually being set correctly.
    public function test_the_deployed_cloudflare_frontend_origin_is_allowed(): void
    {
        $origin = 'https://silverback-sentry-project-uwa-project-frontend.sqmson-mandre.workers.dev';

        $response = $this->postJson('/api/login', [], ['Origin' => $origin]);

        $response->assertHeader('Access-Control-Allow-Origin', $origin);
    }

    public function test_a_renamed_worker_under_the_same_cloudflare_account_is_allowed(): void
    {
        $origin = 'https://wildwatch-portal.sqmson-mandre.workers.dev';

        $response = $this->postJson('/api/login', [], ['Origin' => $origin]);

        $response->assertHeader('Access-Control-Allow-Origin', $origin);
    }

    public function test_an_unrelated_origin_is_not_allowed(): void
    {
        $response = $this->postJson('/api/login', [], ['Origin' => 'https://evil.example.com']);

        $response->assertHeaderMissing('Access-Control-Allow-Origin');
    }
}
