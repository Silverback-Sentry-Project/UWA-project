<?php

use App\Http\Controllers\Api\AuditController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClaimController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EvidenceFormController;
use App\Http\Controllers\Api\EvidenceFormSubmissionController;
use App\Http\Controllers\Api\ForwardedFormController;
use App\Http\Controllers\Api\IncidentController;
use App\Http\Controllers\Api\NewsArticleController;
use App\Http\Controllers\Api\ParkController;
use App\Http\Controllers\Api\RangerController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\SosAlertController;
use App\Http\Controllers\Api\SpeciesController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WebhookController;
use Illuminate\Support\Facades\Route;

// Public
// throttle:6,1 - 6 attempts/minute per IP+email (Laravel's own standard scaffolding default).
// Previously unthrottled (WildWatch-Platform-Plan.md §9.2 W1) - the single entry point to the
// entire portal was brute-forceable with no backoff.
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
Route::get('/public/parks', [ParkController::class, 'publicIndex']);

// Firebase → Laravel bridge (HMAC-protected, outside Sanctum)
// Legacy path: only reachable if a Cloud-Functions relay exists (Blaze plan). Kept for parity/tests.
Route::middleware('webhook.signature')->prefix('webhooks')->group(function () {
    Route::post('/incidents', [WebhookController::class, 'incidents']);
    Route::post('/sightings', [WebhookController::class, 'sightings']);
    Route::post('/sos-alerts', [WebhookController::class, 'sosAlerts']);
});

// Firebase → Laravel bridge, mobile-direct (Spark plan: no Cloud Functions relay, so the mobile
// app calls Laravel itself after writing to Firestore). Authenticated by the caller's own Firebase
// ID token instead of the shared HMAC secret above - see VerifyFirebaseIdToken.
Route::middleware('firebase.idtoken')->prefix('mobile')->group(function () {
    Route::post('/incidents', [WebhookController::class, 'mobileIncidents']);
    Route::post('/sightings', [WebhookController::class, 'mobileSightings']);
    Route::post('/sos-alerts', [WebhookController::class, 'mobileSosAlerts']);
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
    Route::get('/roles', [RoleController::class, 'index']);

    Route::get('/incidents', [IncidentController::class, 'index']);
    Route::post('/incidents', [IncidentController::class, 'store']);
    Route::get('/incidents/{incident}', [IncidentController::class, 'show']);
    Route::patch('/incidents/{incident}/status', [IncidentController::class, 'updateStatus']);
    Route::post('/incidents/{incident}/assign', [IncidentController::class, 'assign']);
    Route::delete('/incidents/{incident}', [IncidentController::class, 'destroy']);

    Route::get('/sos-alerts', [SosAlertController::class, 'index']);
    Route::get('/sos-alerts/{sosAlert}', [SosAlertController::class, 'show']);
    Route::patch('/sos-alerts/{sosAlert}/status', [SosAlertController::class, 'updateStatus']);

    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::post('/users/invite', [UserController::class, 'invite']);
    Route::get('/users/{user}', [UserController::class, 'show']);
    Route::patch('/users/{user}', [UserController::class, 'update']);
});

// News feed authoring — Park Warden / UWA Official only
Route::middleware(['auth:sanctum', 'warden_or_uwa'])->group(function () {
    Route::get('/news-articles', [NewsArticleController::class, 'index']);
    Route::post('/news-articles', [NewsArticleController::class, 'store']);
    Route::get('/news-articles/{newsArticle}', [NewsArticleController::class, 'show']);
});

// Compensation claims and cross-park forwarded-form review — cross-park,
// HQ-level data with no per-park scoping, so restricted to System
// Administrator and UWA Official (narrower than the 'admin' alias, which
// also admits Park Warden and Gamepark Officer).
Route::middleware(['auth:sanctum', 'admin_or_uwa'])->group(function () {
    Route::get('/claims', [ClaimController::class, 'index']);
    Route::get('/claims/{claim}', [ClaimController::class, 'show']);
    Route::post('/claims/{claim}/approve', [ClaimController::class, 'approve']);
    Route::post('/claims/{claim}/reject', [ClaimController::class, 'reject']);
    Route::post('/claims/{claim}/mark-paid', [ClaimController::class, 'markPaid']);

    Route::get('/forwarded-forms', [ForwardedFormController::class, 'index']);
    Route::get('/forwarded-forms/{submission}', [ForwardedFormController::class, 'show']);
});

// Gamepark-portal-only routes — actions unique to running a single park's
// operations: dispatching against emergencies, evidence forms, and inviting
// the park's own field staff. Every query here is scoped to the signed-in
// account's own park_id inside the controller.
Route::middleware(['auth:sanctum', 'gamepark'])->group(function () {
    Route::post('/gamepark/incidents/{incident}/assign', [IncidentController::class, 'assign']);

    Route::get('/gamepark/forms', [EvidenceFormController::class, 'index']);
    Route::post('/gamepark/forms', [EvidenceFormController::class, 'store']);
    Route::get('/gamepark/forms/{form}', [EvidenceFormController::class, 'show']);
    Route::patch('/gamepark/forms/{form}', [EvidenceFormController::class, 'update']);
    Route::delete('/gamepark/forms/{form}', [EvidenceFormController::class, 'destroy']);

    Route::get('/gamepark/submissions', [EvidenceFormSubmissionController::class, 'index']);
    Route::get('/gamepark/submissions/{submission}', [EvidenceFormSubmissionController::class, 'show']);
    Route::post('/gamepark/submissions/{submission}/verify', [EvidenceFormSubmissionController::class, 'verify']);
    Route::post('/gamepark/submissions/{submission}/forward', [EvidenceFormSubmissionController::class, 'forward']);

    Route::get('/gamepark/personnel', [UserController::class, 'gameparkIndex']);
    Route::post('/gamepark/personnel/invite', [UserController::class, 'gameparkInvite']);
});
