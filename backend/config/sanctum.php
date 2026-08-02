<?php

use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Laravel\Sanctum\Http\Middleware\AuthenticateSession;
use Laravel\Sanctum\Sanctum;

$statefulDomains = array_values(array_unique(array_filter(array_merge(
    array_map('trim', explode(',', env(
        'SANCTUM_STATEFUL_DOMAINS',
        'localhost,localhost:5173,127.0.0.1,127.0.0.1:5173,127.0.0.1:8000,::1'
    ))),
    wildwatch_lan_host_domains(),
))));

return [

    'stateful' => $statefulDomains,

    'guard' => ['web'],

    'expiration' => null,

    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),

    'middleware' => [
        'authenticate_session' => AuthenticateSession::class,
        'encrypt_cookies' => EncryptCookies::class,
        'validate_csrf_token' => ValidateCsrfToken::class,
    ],

];
