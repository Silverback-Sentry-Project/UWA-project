<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // CORS_ALLOWED_ORIGINS is a Render dashboard env var (sync: false in render.yaml) - it
    // was either never set or drifted out of sync with the actual deployed Cloudflare Workers
    // origin, confirmed live 2026-08-13 (browser console: "CORS header 'Access-Control-Allow-
    // Origin' missing" on /api/login, even though curl - which doesn't enforce CORS at all -
    // showed the endpoint itself working fine). Rather than depend entirely on that dashboard
    // value being correct, the known real production frontend origin is now also hardcoded
    // below as a floor that always works regardless of Render dashboard state; the env var is
    // additive on top of it, not a replacement for it. Worker renamed from
    // silverback-sentry-project-uwa-project-frontend to wildwatch-portal the same day - the
    // *.sqmson-mandre.workers.dev pattern below already covers either name, this literal
    // entry is just for exact-match clarity on the current one.
    'allowed_origins' => array_values(array_unique(array_filter(array_merge(
        ['https://wildwatch-portal.sqmson-mandre.workers.dev'],
        env('CORS_ALLOWED_ORIGINS')
            ? array_map('trim', explode(',', env('CORS_ALLOWED_ORIGINS')))
            : [],
    )))),

    'allowed_origins_patterns' => array_values(array_filter([
        '#^https?://localhost(:\d+)?$#',
        '#^https?://127\.0\.0\.1(:\d+)?$#',
        '#^https?://\[::1\](:\d+)?$#',
        '#^https?://(10\.\d+\.\d+\.\d+|172\.(1[6-9]|2\d|3[01])\.\d+\.\d+|192\.168\.\d+\.\d+)(:\d+)?$#',
        env('DEV_LAN_HOST') ? '#^https?://'.preg_quote(env('DEV_LAN_HOST'), '#').'(:\d+)?$#' : null,
        // Any *.workers.dev subdomain under this specific Cloudflare account - covers a
        // renamed Worker (or a preview/staging deployment under a different script name)
        // without needing a Render dashboard env var update every time the Worker name changes.
        // Scoped to this one account's subdomain, not all of workers.dev, deliberately.
        '#^https://[a-z0-9-]+\.sqmson-mandre\.workers\.dev$#',
    ])),

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
