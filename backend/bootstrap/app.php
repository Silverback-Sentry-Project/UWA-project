<?php

use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureAdminOrUwaOfficial;
use App\Http\Middleware\EnsureGamepark;
use App\Http\Middleware\EnsureWardenOrUwaOfficial;
use App\Http\Middleware\VerifyWebhookSignature;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin' => EnsureAdmin::class,
            'webhook.signature' => VerifyWebhookSignature::class,
            'warden_or_uwa' => EnsureWardenOrUwaOfficial::class,
            'gamepark' => EnsureGamepark::class,
            'admin_or_uwa' => EnsureAdminOrUwaOfficial::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
