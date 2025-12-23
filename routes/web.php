<?php

use App\Http\Controllers\App\CustomerController;
use App\Http\Controllers\App\QuoteApprovalController;
use App\Http\Controllers\App\QuoteController;
use App\Http\Controllers\App\VehicleController;
use App\Http\Controllers\App\WorkOrderController;
use App\Http\Controllers\App\SettingsController;
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
    Route::apiResource('vehicles', VehicleController::class);
    Route::apiResource('work-orders', WorkOrderController::class);
    
    // Quotes
    Route::get('/quotes', [QuoteController::class, 'index'])->name('app.quotes.index');
    Route::post('/quotes', [QuoteController::class, 'store'])->name('app.quotes.store');
    Route::put('/quotes/{quote}', [QuoteController::class, 'update'])->name('app.quotes.update');
    Route::delete('/quotes/{quote}', [QuoteController::class, 'destroy'])->name('app.quotes.destroy');
    Route::post('/quotes/{quote}/approve', [QuoteApprovalController::class, 'approve'])->name('app.quotes.approve');
    Route::post('/quotes/{quote}/reject', [QuoteApprovalController::class, 'reject'])->name('app.quotes.reject');
    Route::get('/quotes/search', [QuoteController::class, 'search'])->name('app.quotes.search');
    Route::get('/quotes/{quote}', [QuoteController::class, 'show'])->name('app.quotes.show');
    Route::post('/quotes/{quote}/services', [QuoteController::class, 'addService'])->name('app.quotes.services.store');
    Route::put('/quotes/{quote}/services/{line}', [QuoteController::class, 'updateService'])->name('app.quotes.services.update');
    Route::delete('/quotes/{quote}/services/{line}', [QuoteController::class, 'deleteService'])->name('app.quotes.services.destroy');
    Route::post('/quotes/{quote}/departments', [QuoteController::class, 'addDepartment'])->name('app.quotes.departments.store');
    Route::delete('/quotes/{quote}/departments/{department}', [QuoteController::class, 'removeDepartment'])->name('app.quotes.departments.destroy');
    
    // Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    
    // System Settings
    Route::get('/settings/system', [\App\Http\Controllers\App\SystemSettingsController::class, 'index'])->name('settings.system');
    
    // Vehicle Makes
    Route::get('/settings/makes', [\App\Http\Controllers\App\VehicleMakeController::class, 'index'])->name('settings.makes.index');
    Route::post('/settings/makes', [\App\Http\Controllers\App\VehicleMakeController::class, 'store'])->name('settings.makes.store');
    Route::put('/settings/makes/{make}', [\App\Http\Controllers\App\VehicleMakeController::class, 'update'])->name('settings.makes.update');
    Route::delete('/settings/makes/{make}', [\App\Http\Controllers\App\VehicleMakeController::class, 'destroy'])->name('settings.makes.destroy');
    Route::patch('/settings/makes/{make}/toggle-active', [\App\Http\Controllers\App\VehicleMakeController::class, 'toggleActive'])->name('settings.makes.toggle');
    
    // Vehicle Models
    Route::get('/settings/models', [\App\Http\Controllers\App\VehicleModelController::class, 'index'])->name('settings.models.index');
    Route::post('/settings/models', [\App\Http\Controllers\App\VehicleModelController::class, 'store'])->name('settings.models.store');
    Route::put('/settings/models/{model}', [\App\Http\Controllers\App\VehicleModelController::class, 'update'])->name('settings.models.update');
    Route::delete('/settings/models/{model}', [\App\Http\Controllers\App\VehicleModelController::class, 'destroy'])->name('settings.models.destroy');
    Route::patch('/settings/models/{model}/toggle-active', [\App\Http\Controllers\App\VehicleModelController::class, 'toggleActive'])->name('settings.models.toggle');
    
    // Vehicle Colors
    Route::get('/settings/colors', [\App\Http\Controllers\App\VehicleColorController::class, 'index'])->name('settings.colors.index');
    Route::post('/settings/colors', [\App\Http\Controllers\App\VehicleColorController::class, 'store'])->name('settings.colors.store');
    Route::put('/settings/colors/{color}', [\App\Http\Controllers\App\VehicleColorController::class, 'update'])->name('settings.colors.update');
    Route::delete('/settings/colors/{color}', [\App\Http\Controllers\App\VehicleColorController::class, 'destroy'])->name('settings.colors.destroy');
    Route::patch('/settings/colors/{color}/toggle-active', [\App\Http\Controllers\App\VehicleColorController::class, 'toggleActive'])->name('settings.colors.toggle');
    
    // Departments
    Route::apiResource('departments', \App\Http\Controllers\App\DepartmentController::class);
    Route::patch('/departments/{department}/toggle-active', [\App\Http\Controllers\App\DepartmentController::class, 'toggleActive']);

    // Services
    Route::apiResource('services', \App\Http\Controllers\App\ServiceController::class);
    Route::patch('/services/{service}/toggle-active', [\App\Http\Controllers\App\ServiceController::class, 'toggleActive']);
    
    // API endpoints
    Route::get('/api/customers', [CustomerController::class, 'apiIndex']);
    Route::get('/api/customers/search', [WorkOrderController::class, 'apiCustomerSearch']);
    Route::get('/api/vehicles', [WorkOrderController::class, 'apiVehicles']);
    Route::get('/api/work-orders', [WorkOrderController::class, 'apiIndex']);
    Route::get('/api/services', [\App\Http\Controllers\App\ServiceController::class, 'apiList']);
    Route::get('/api/makes', [\App\Http\Controllers\App\VehicleMakeController::class, 'apiList']);
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
