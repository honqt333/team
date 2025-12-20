<?php

use App\Http\Controllers\App\CustomerController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// App routes (authenticated + tenancy)
Route::prefix('app')->middleware(['auth', 'tenant.active', 'center.context'])->group(function () {
    Route::apiResource('customers', CustomerController::class);
    
    // API endpoint for testing
    Route::get('/api/customers', [CustomerController::class, 'apiIndex']);
});

// Test routes for tenancy middleware (testing environment only)
if (app()->environment('testing')) {
    Route::get('/__test/tenancy-ping', function () {
        return 'ok';
    })->middleware(['auth', 'tenant.active', 'center.context']);

    Route::post('/__test/tenancy-write', function () {
        return 'ok';
    })->middleware(['auth', 'tenant.active', 'center.context']);
}

require __DIR__.'/auth.php';
