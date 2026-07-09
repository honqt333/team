<?php

use App\Http\Controllers\Api\AiDemoController;
use App\Http\Controllers\Api\HR\BiometricAttendanceController;
use App\Http\Controllers\HealthController;
use App\Http\Middleware\TrackAiUsage;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
|
*/

// ---------------------------------------------------------------------------
// Health & readiness probes — Track C — Observability
//
// Mounted OUTSIDE any middleware group so:
//   * No auth/tenant/scope required (probes must work for k8s livenessProbe
//     which has no credentials).
//   * No rate-limit throttle (LB pings every 5–10s — would hit limits).
//   * No CSRF (not session-bound).
//
// k8s manifest snippet:
//   livenessProbe:
//     httpGet: { path: /healthz, port: http }
//     initialDelaySeconds: 5, periodSeconds: 10
//   readinessProbe:
//     httpGet: { path: /readyz, port: http }
//     initialDelaySeconds: 5, periodSeconds: 10
//     failureThreshold: 3
// ---------------------------------------------------------------------------
Route::get('/healthz', [HealthController::class, 'live'])->name('api.healthz');
Route::get('/readyz', [HealthController::class, 'ready'])->name('api.readyz');

// Biometric Attendance API (v1)
Route::prefix('v1/attendance')->middleware('throttle:30,1')->group(function () {
    Route::post('/clock', [BiometricAttendanceController::class, 'clock']);
    Route::post('/batch', [BiometricAttendanceController::class, 'batch'])->middleware('throttle:10,1');
    Route::get('/ping', [BiometricAttendanceController::class, 'ping']);
});

Route::post('/v1/ai/demo/describe-vehicle', [AiDemoController::class, 'describe'])
    ->middleware(['auth:sanctum', TrackAiUsage::class, 'tenant.active'])
    ->name('api.ai.demo.describe');
