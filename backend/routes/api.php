<?php

use App\Http\Controllers\Api\AuditController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClaimController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\IncidentController;
use App\Http\Controllers\Api\ParkController;
use App\Http\Controllers\Api\RangerController;
use App\Http\Controllers\Api\SosAlertController;
use App\Http\Controllers\Api\SpeciesController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// Public
Route::post('/login', [AuthController::class, 'login']);

// Authenticated admin-portal routes
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

    Route::get('/parks', [ParkController::class, 'index']);
    Route::get('/species', [SpeciesController::class, 'index']);
    Route::get('/rangers', [RangerController::class, 'index']);
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
