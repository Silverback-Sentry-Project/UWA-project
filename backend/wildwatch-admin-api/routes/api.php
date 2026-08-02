<?php

use App\Http\Controllers\Api\AuditController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClaimController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EvidenceFormController;
use App\Http\Controllers\Api\EvidenceFormSubmissionController;
use App\Http\Controllers\Api\ForwardedFormController;
use App\Http\Controllers\Api\IncidentController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ParkController;
use App\Http\Controllers\Api\RangerController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\SosAlertController;
use App\Http\Controllers\Api\SpeciesController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// Public
Route::post('/login', [AuthController::class, 'login']);
Route::get('/public/parks', [ParkController::class, 'publicIndex']);

// Shared across both portals — any authenticated account (UWA or Gamepark)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead']);
});

// Shared "portal" routes — either a UWA admin or a Gamepark account may use
// these. There is no role-based blocking here beyond that; the only
// restriction is data scoping, enforced inside each controller: a Gamepark
// account only ever sees/affects records belonging to its own park.
Route::middleware(['auth:sanctum', 'portal'])->group(function () {
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

    Route::get('/parks', [ParkController::class, 'index']);
    Route::get('/species', [SpeciesController::class, 'index']);
    Route::get('/rangers', [RangerController::class, 'index']);
    Route::get('/audit', [AuditController::class, 'index']);
    Route::get('/roles', [RoleController::class, 'index']);

    Route::get('/incidents', [IncidentController::class, 'index']);
    Route::post('/incidents', [IncidentController::class, 'store']);
    Route::get('/incidents/{incident}', [IncidentController::class, 'show']);
    Route::patch('/incidents/{incident}/status', [IncidentController::class, 'updateStatus']);

    Route::get('/sos-alerts', [SosAlertController::class, 'index']);
    Route::get('/sos-alerts/{sosAlert}', [SosAlertController::class, 'show']);
    Route::patch('/sos-alerts/{sosAlert}/status', [SosAlertController::class, 'updateStatus']);
});

// Authenticated admin-portal-only routes — System Administrators.
// Compensation, personnel management, and cross-park visibility (forwarded
// forms) stay restricted here; everything else that's just "park data" now
// lives in the shared 'portal' group above.
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::delete('/incidents/{incident}', [IncidentController::class, 'destroy']);

    Route::get('/claims', [ClaimController::class, 'index']);
    Route::get('/claims/{claim}', [ClaimController::class, 'show']);
    Route::post('/claims/{claim}/approve', [ClaimController::class, 'approve']);
    Route::post('/claims/{claim}/reject', [ClaimController::class, 'reject']);
    Route::post('/claims/{claim}/mark-paid', [ClaimController::class, 'markPaid']);

    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::post('/users/invite', [UserController::class, 'invite']);
    Route::get('/users/{user}', [UserController::class, 'show']);
    Route::patch('/users/{user}', [UserController::class, 'update']);

    // Verified forms forwarded from any gamepark land here as new items.
    Route::get('/forwarded-forms', [ForwardedFormController::class, 'index']);
    Route::get('/forwarded-forms/{submission}', [ForwardedFormController::class, 'show']);
});

// Authenticated gamepark-portal-only routes — actions unique to running a
// single park's operations: dispatching against emergencies, evidence forms,
// and inviting the park's own field staff.
Route::middleware(['auth:sanctum', 'gamepark'])->group(function () {
    Route::post('/gamepark/incidents/{incident}/assign', [IncidentController::class, 'assign']);

    // Forms submodule — create, view, edit evidence form templates.
    Route::get('/gamepark/forms', [EvidenceFormController::class, 'index']);
    Route::post('/gamepark/forms', [EvidenceFormController::class, 'store']);
    Route::get('/gamepark/forms/{form}', [EvidenceFormController::class, 'show']);
    Route::patch('/gamepark/forms/{form}', [EvidenceFormController::class, 'update']);
    Route::delete('/gamepark/forms/{form}', [EvidenceFormController::class, 'destroy']);

    // Submissions submodule — review, verify, and forward filled-in forms.
    Route::get('/gamepark/submissions', [EvidenceFormSubmissionController::class, 'index']);
    Route::get('/gamepark/submissions/{submission}', [EvidenceFormSubmissionController::class, 'show']);
    Route::post('/gamepark/submissions/{submission}/verify', [EvidenceFormSubmissionController::class, 'verify']);
    Route::post('/gamepark/submissions/{submission}/forward', [EvidenceFormSubmissionController::class, 'forward']);

    // A gamepark's own field-staff roster and invite flow (Ranger, Community
    // Wildlife Officer, Park Warden only — always pinned to this park).
    Route::get('/gamepark/personnel', [UserController::class, 'gameparkIndex']);
    Route::post('/gamepark/personnel/invite', [UserController::class, 'gameparkInvite']);
});
