<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\HR\BiometricAttendanceController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
|
*/

// Biometric Attendance API (v1)
Route::prefix('v1/attendance')->group(function () {
    Route::post('/clock', [BiometricAttendanceController::class, 'clock']);
    Route::post('/batch', [BiometricAttendanceController::class, 'batch']);
    Route::get('/ping', [BiometricAttendanceController::class, 'ping']);
});
