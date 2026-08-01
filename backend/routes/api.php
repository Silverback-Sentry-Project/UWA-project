<?php

use App\Http\Controllers\Api\AuditController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClaimController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\IncidentController;
use App\Http\Controllers\Api\NewsArticleController;
use App\Http\Controllers\Api\ParkController;
use App\Http\Controllers\Api\RangerController;
use App\Http\Controllers\Api\SosAlertController;
use App\Http\Controllers\Api\SpeciesController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WebhookController;
use Illuminate\Support\Facades\Route;

// Public
Route::post('/login', [AuthController::class, 'login']);

// Firebase → Laravel bridge (HMAC-protected, outside Sanctum)
Route::middleware('webhook.signature')->prefix('webhooks')->group(function () {
    Route::post('/incidents', [WebhookController::class, 'incidents']);
    Route::post('/sightings', [WebhookController::class, 'sightings']);
    Route::post('/sos-alerts', [WebhookController::class, 'sosAlerts']);
});

// Authenticated admin-portal routes
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

    Route::get('/parks', [ParkController::class, 'index']);
    Route::get('/species', [SpeciesController::class, 'index']);
    Route::get('/rangers', [RangerController::class, 'index']);
    Route::post('/rangers', [RangerController::class, 'store']);
    Route::get('/audit', [AuditController::class, 'index']);

    Route::get('/incidents', [IncidentController::class, 'index']);
    Route::post('/incidents', [IncidentController::class, 'store']);
    Route::get('/incidents/{incident}', [IncidentController::class, 'show']);
    Route::patch('/incidents/{incident}/status', [IncidentController::class, 'updateStatus']);
    Route::post('/incidents/{incident}/assign', [IncidentController::class, 'assign']);
    Route::delete('/incidents/{incident}', [IncidentController::class, 'destroy']);

    Route::get('/sos-alerts', [SosAlertController::class, 'index']);
    Route::get('/sos-alerts/{sosAlert}', [SosAlertController::class, 'show']);
    Route::patch('/sos-alerts/{sosAlert}/status', [SosAlertController::class, 'updateStatus']);

    Route::get('/claims', [ClaimController::class, 'index']);
    Route::get('/claims/{claim}', [ClaimController::class, 'show']);
    Route::post('/claims/{claim}/approve', [ClaimController::class, 'approve']);
    Route::post('/claims/{claim}/reject', [ClaimController::class, 'reject']);
    Route::post('/claims/{claim}/mark-paid', [ClaimController::class, 'markPaid']);

    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::get('/users/{user}', [UserController::class, 'show']);
    Route::patch('/users/{user}', [UserController::class, 'update']);
});

// News feed authoring — Park Warden / UWA Official only
Route::middleware(['auth:sanctum', 'warden_or_uwa'])->group(function () {
    Route::get('/news-articles', [NewsArticleController::class, 'index']);
    Route::post('/news-articles', [NewsArticleController::class, 'store']);
    Route::get('/news-articles/{newsArticle}', [NewsArticleController::class, 'show']);
});
