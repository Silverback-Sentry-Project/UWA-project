<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => env('CORS_ALLOWED_ORIGINS')
        ? array_values(array_filter(array_map('trim', explode(',', env('CORS_ALLOWED_ORIGINS')))))
        : [],

    'allowed_origins_patterns' => array_values(array_filter([
        '#^https?://localhost(:\d+)?$#',
        '#^https?://127\.0\.0\.1(:\d+)?$#',
        '#^https?://\[::1\](:\d+)?$#',
        '#^https?://(10\.\d+\.\d+\.\d+|172\.(1[6-9]|2\d|3[01])\.\d+\.\d+|192\.168\.\d+\.\d+)(:\d+)?$#',
        env('DEV_LAN_HOST') ? '#^https?://'.preg_quote(env('DEV_LAN_HOST'), '#').'(:\d+)?$#' : null,
    ])),

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
