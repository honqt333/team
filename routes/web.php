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
    Route::post('/profile/switch-center', [ProfileController::class, 'switchCenter'])->name('profile.switch-center');
});

// App routes (authenticated + tenancy)
Route::prefix('app')->middleware(['auth', 'tenant.active', 'center.context'])->group(function () {
    // Customer export/import routes (must be before resource routes)
    Route::get('/customers/export', [CustomerController::class, 'export'])->name('customers.export');
    Route::get('/customers/print', [CustomerController::class, 'print'])->name('customers.print');
    Route::get('/customers/import/template', [CustomerController::class, 'downloadTemplate'])->name('customers.import.template');
    Route::post('/customers/import', [CustomerController::class, 'import'])->name('customers.import');
    
    Route::resource('customers', CustomerController::class);
    // Customer merge routes
    Route::get('/customers/{customer}/merge', [CustomerController::class, 'mergeData'])->name('customers.merge-data');
    Route::post('/customers/{source}/merge/{target}', [CustomerController::class, 'executeMerge'])->name('customers.execute-merge');

    // Vehicle export/print routes
    Route::get('/vehicles/export', [VehicleController::class, 'export'])->name('vehicles.export');
    Route::get('/vehicles/print', [VehicleController::class, 'print'])->name('vehicles.print');
    Route::apiResource('vehicles', VehicleController::class);
    
    // Work Orders - Hub and Index
    Route::get('/work-orders', [WorkOrderController::class, 'hub'])->name('work-orders.hub');
    Route::get('/work-orders/list', [WorkOrderController::class, 'index'])->name('work-orders.index');
    Route::post('/work-orders', [WorkOrderController::class, 'store'])->name('work-orders.store');
    Route::get('/work-orders/{workOrder}', [WorkOrderController::class, 'show'])->name('work-orders.show');
    Route::put('/work-orders/{workOrder}', [WorkOrderController::class, 'update'])->name('work-orders.update');
    Route::delete('/work-orders/{workOrder}', [WorkOrderController::class, 'destroy'])->name('work-orders.destroy');
    
    // Work Order Items (Services)
    Route::post('/work-orders/{work_order}/items', [WorkOrderController::class, 'addItem'])->name('work-orders.items.store');
    Route::put('/work-orders/{work_order}/items/{item}', [WorkOrderController::class, 'updateItem'])->name('work-orders.items.update');
    Route::delete('/work-orders/{work_order}/items/{item}', [WorkOrderController::class, 'deleteItem'])->name('work-orders.items.destroy');
    
    // Work Order Departments
    Route::post('/work-orders/{work_order}/departments', [WorkOrderController::class, 'addDepartment'])->name('work-orders.departments.store');
    Route::delete('/work-orders/{work_order}/departments/{department}', [WorkOrderController::class, 'removeDepartment'])->name('work-orders.departments.destroy');
    
    // Work Order Parts (Inventory Integration)
    Route::post('/work-orders/{workOrder}/parts', [\App\Http\Controllers\App\WorkOrderPartsController::class, 'store'])->name('work-orders.parts.store');
    Route::put('/work-order-parts/{workOrderPart}', [\App\Http\Controllers\App\WorkOrderPartsController::class, 'update'])->name('work-orders.parts.update');
    Route::delete('/work-order-parts/{workOrderPart}', [\App\Http\Controllers\App\WorkOrderPartsController::class, 'destroy'])->name('work-orders.parts.destroy');
    Route::post('/work-order-parts/{workOrderPart}/reverse', [\App\Http\Controllers\App\WorkOrderPartsController::class, 'reverse'])->name('work-orders.parts.reverse');
    Route::get('/api/work-order-parts/check-stock', [\App\Http\Controllers\App\WorkOrderPartsController::class, 'checkStock'])->name('work-orders.parts.check-stock');
    
    // Work Order Status Management
    Route::post('/work-orders/{work_order}/hold', [WorkOrderController::class, 'putOnHold'])->name('work-orders.hold');
    Route::post('/work-orders/{work_order}/resume', [WorkOrderController::class, 'resume'])->name('work-orders.resume');
    Route::post('/work-orders/{work_order}/cancel', [WorkOrderController::class, 'cancel'])->name('work-orders.cancel');
    Route::post('/work-orders/{work_order}/complete', [WorkOrderController::class, 'complete'])->name('work-orders.complete');
    
    // Work Order Print Routes
    Route::get('/work-orders/{workOrder}/print/condition', [WorkOrderController::class, 'printCondition'])->name('work-orders.print.condition');
    Route::get('/work-orders/{workOrder}/print/services', [WorkOrderController::class, 'printServices'])->name('work-orders.print.services');
    Route::get('/work-orders/{workOrder}/print/proforma', [WorkOrderController::class, 'printProforma'])->name('work-orders.print.proforma');
    Route::get('/work-orders/{workOrder}/print/payments', [WorkOrderController::class, 'printPayments'])->name('work-orders.print.payments');
    
    // Work Order Payments
    Route::post('/work-orders/{workOrder}/payments', [WorkOrderController::class, 'storePayment'])->name('work-orders.payments.store');
    Route::put('/work-orders/{workOrder}/payments/{payment}', [WorkOrderController::class, 'updatePayment'])->name('work-orders.payments.update');
    Route::delete('/work-orders/{workOrder}/payments/{payment}', [WorkOrderController::class, 'destroyPayment'])->name('work-orders.payments.destroy');
    
    // Work Order Condition Report (Fuel Level & Damage Marks)
    Route::put('/work-orders/{workOrder}/condition', [WorkOrderController::class, 'updateCondition'])->name('app.work-orders.update-condition');
    
    // Work Order Item Status
    Route::patch('/work-orders/{work_order}/items/{item}/status', [WorkOrderController::class, 'updateItemStatus'])->name('work-orders.items.status');
    
    // Work Order Item Technicians
    Route::post('/work-orders/{work_order}/items/{item}/technicians', [WorkOrderController::class, 'assignTechnician'])->name('work-orders.items.technicians.store');
    Route::delete('/work-orders/{work_order}/items/{item}/technicians/{user}', [WorkOrderController::class, 'removeTechnician'])->name('work-orders.items.technicians.destroy');
    
    // Work Order Item Parts
    Route::post('/work-orders/{work_order}/items/{item}/parts', [WorkOrderController::class, 'addPart'])->name('work-orders.items.parts.store');
    Route::put('/work-orders/{work_order}/items/{item}/parts/{part}', [WorkOrderController::class, 'updatePart'])->name('work-orders.items.parts.update');
    Route::delete('/work-orders/{work_order}/items/{item}/parts/{part}', [WorkOrderController::class, 'deletePart'])->name('work-orders.items.parts.destroy');
    
    // Work Order Item Notes
    Route::post('/work-orders/{work_order}/items/{item}/notes', [WorkOrderController::class, 'addNote'])->name('work-orders.items.notes.store');
    Route::delete('/work-orders/{work_order}/items/{item}/notes/{note}', [WorkOrderController::class, 'deleteNote'])->name('work-orders.items.notes.destroy');
    
    // Work Order Photos
    Route::delete('/work-orders/{workOrder}/photos/{photo}', [WorkOrderController::class, 'deletePhoto'])->name('work-orders.photos.destroy');
    
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
    
    // Company Profile Settings
    Route::get('/settings/company', [\App\Http\Controllers\App\CompanyProfileController::class, 'index'])->name('settings.company');
    Route::put('/settings/company', [\App\Http\Controllers\App\CompanyProfileController::class, 'update'])->name('settings.company.update');
    Route::put('/settings/company/admin-user', [\App\Http\Controllers\App\CompanyProfileController::class, 'updateAdminUser'])->name('settings.company.admin-user');
    Route::post('/settings/company/logo', [\App\Http\Controllers\App\CompanyProfileController::class, 'uploadLogo'])->name('settings.company.logo.upload');
    Route::delete('/settings/company/logo', [\App\Http\Controllers\App\CompanyProfileController::class, 'deleteLogo'])->name('settings.company.logo.delete');
    
    // Branches Settings
    Route::get('/settings/branches', [\App\Http\Controllers\App\BranchesController::class, 'index'])->name('settings.branches');
    
    // Center Settings
    Route::get('/settings/centers/{center}', [\App\Http\Controllers\App\CenterSettingsController::class, 'index'])->name('settings.centers.show');
    Route::put('/settings/centers/{center}', [\App\Http\Controllers\App\CenterSettingsController::class, 'update'])->name('settings.centers.update');
    Route::post('/settings/centers/{center}/logo', [\App\Http\Controllers\App\CenterSettingsController::class, 'uploadLogo'])->name('settings.centers.logo.upload');
    Route::delete('/settings/centers/{center}/logo', [\App\Http\Controllers\App\CenterSettingsController::class, 'deleteLogo'])->name('settings.centers.logo.delete');
    Route::post('/settings/centers/{center}/stamp', [\App\Http\Controllers\App\CenterSettingsController::class, 'uploadStamp'])->name('settings.centers.stamp.upload');
    Route::delete('/settings/centers/{center}/stamp', [\App\Http\Controllers\App\CenterSettingsController::class, 'deleteStamp'])->name('settings.centers.stamp.delete');
    
    // System Settings
    Route::get('/settings/system', [\App\Http\Controllers\App\SystemSettingsController::class, 'index'])->name('settings.system');

    // Users Settings
    Route::resource('settings/users', \App\Http\Controllers\App\UserController::class)->names('settings.users');
    Route::patch('/settings/users/{user}/toggle-active', [\App\Http\Controllers\App\UserController::class, 'toggleActive'])->name('settings.users.toggle');
    
    // Roles Settings
    Route::resource('settings/roles', \App\Http\Controllers\App\RoleController::class)->names('settings.roles');
    
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
    
    // API endpoints (Refactored to separate Controller)
    Route::get('/api/customers', [CustomerController::class, 'apiIndex']);
    Route::get('/api/vehicles-index', [VehicleController::class, 'apiIndex'])->name('vehicles.api.index');
    Route::get('/api/quotes-index', [QuoteController::class, 'apiIndex'])->name('quotes.api.index');
    Route::get('/api/work-orders-index', [WorkOrderController::class, 'apiIndex'])->name('work-orders.api.index');
    Route::get('/api/customers/search', [\App\Http\Controllers\Api\WorkOrderController::class, 'customerSearch']);
    Route::get('/api/vehicles', [\App\Http\Controllers\Api\WorkOrderController::class, 'customerVehicles']);
    Route::get('/api/vehicles/search', [\App\Http\Controllers\Api\WorkOrderController::class, 'vehicleSearch']);
    Route::get('/api/work-orders', [\App\Http\Controllers\Api\WorkOrderController::class, 'index']);
    Route::get('/api/services', [\App\Http\Controllers\App\ServiceController::class, 'apiList']);
    Route::get('/api/makes', [\App\Http\Controllers\App\VehicleMakeController::class, 'apiList']);
    
    // ───────────────────────────────────────────────────────────────
    // Inventory Module
    // ───────────────────────────────────────────────────────────────
    Route::prefix('inventory')->name('app.inventory.')->group(function () {
        // Hub (main dashboard)
        Route::get('/', [\App\Http\Controllers\App\InventorySettingsController::class, 'hub'])->name('hub');
        
        // Settings (units, categories)
        Route::get('/settings', [\App\Http\Controllers\App\InventorySettingsController::class, 'index'])->name('settings.index');
        Route::post('/settings/units', [\App\Http\Controllers\App\InventorySettingsController::class, 'storeUnit'])->name('settings.units.store');
        Route::put('/settings/units/{unit}', [\App\Http\Controllers\App\InventorySettingsController::class, 'updateUnit'])->name('settings.units.update');
        Route::delete('/settings/units/{unit}', [\App\Http\Controllers\App\InventorySettingsController::class, 'destroyUnit'])->name('settings.units.destroy');
        Route::post('/settings/categories', [\App\Http\Controllers\App\InventorySettingsController::class, 'storeCategory'])->name('settings.categories.store');
        Route::put('/settings/categories/{category}', [\App\Http\Controllers\App\InventorySettingsController::class, 'updateCategory'])->name('settings.categories.update');
        Route::delete('/settings/categories/{category}', [\App\Http\Controllers\App\InventorySettingsController::class, 'destroyCategory'])->name('settings.categories.destroy');
        
        // Parts
        Route::get('/parts', [\App\Http\Controllers\App\PartsController::class, 'index'])->name('parts.index');
        Route::get('/parts/create', [\App\Http\Controllers\App\PartsController::class, 'create'])->name('parts.create');
        Route::post('/parts', [\App\Http\Controllers\App\PartsController::class, 'store'])->name('parts.store');
        Route::get('/parts/{part}/edit', [\App\Http\Controllers\App\PartsController::class, 'edit'])->name('parts.edit');
        Route::put('/parts/{part}', [\App\Http\Controllers\App\PartsController::class, 'update'])->name('parts.update');
        Route::patch('/parts/{part}/toggle', [\App\Http\Controllers\App\PartsController::class, 'toggleActive'])->name('parts.toggle');
        Route::get('/api/parts/search', [\App\Http\Controllers\App\PartsController::class, 'search'])->name('parts.search');
        
        // Stock Balances
        Route::get('/stock', [\App\Http\Controllers\App\InventoryBalanceController::class, 'index'])->name('stock.index');
        Route::get('/api/stock/{part}', [\App\Http\Controllers\App\InventoryBalanceController::class, 'getPartStock'])->name('stock.part');
        
        // Inventory Moves (Ledger)
        Route::get('/moves', [\App\Http\Controllers\App\InventoryMoveController::class, 'index'])->name('moves.index');
        Route::post('/moves/receipt', [\App\Http\Controllers\App\InventoryMoveController::class, 'storeReceipt'])->name('moves.receipt');
        Route::post('/moves/adjustment', [\App\Http\Controllers\App\InventoryMoveController::class, 'storeAdjustment'])->name('moves.adjustment');
        Route::post('/moves/{inventoryMove}/reverse', [\App\Http\Controllers\App\InventoryMoveController::class, 'reverse'])->name('moves.reverse');
        
        // Inventory Transfers
        Route::get('/transfers', [\App\Http\Controllers\App\InventoryTransfersController::class, 'index'])->name('transfers.index');
        Route::get('/transfers/create', [\App\Http\Controllers\App\InventoryTransfersController::class, 'create'])->name('transfers.create');
        Route::post('/transfers', [\App\Http\Controllers\App\InventoryTransfersController::class, 'store'])->name('transfers.store');
        Route::get('/transfers/{transfer}', [\App\Http\Controllers\App\InventoryTransfersController::class, 'show'])->name('transfers.show');
        Route::post('/transfers/{transfer}/items', [\App\Http\Controllers\App\InventoryTransfersController::class, 'addItem'])->name('transfers.items.store');
        Route::delete('/transfers/{transfer}/items/{item}', [\App\Http\Controllers\App\InventoryTransfersController::class, 'removeItem'])->name('transfers.items.destroy');
        Route::post('/transfers/{transfer}/send', [\App\Http\Controllers\App\InventoryTransfersController::class, 'send'])->name('transfers.send');
        Route::post('/transfers/{transfer}/receive', [\App\Http\Controllers\App\InventoryTransfersController::class, 'receive'])->name('transfers.receive');
        Route::post('/transfers/{transfer}/cancel', [\App\Http\Controllers\App\InventoryTransfersController::class, 'cancel'])->name('transfers.cancel');
    });
    
    // ───────────────────────────────────────────────────────────────
    // Purchasing Module
    // ───────────────────────────────────────────────────────────────
    Route::prefix('purchasing')->name('app.purchasing.')->group(function () {
        // Suppliers
        Route::get('/suppliers', [\App\Http\Controllers\App\SuppliersController::class, 'index'])->name('suppliers.index');
        Route::get('/suppliers/export', [\App\Http\Controllers\App\SuppliersController::class, 'export'])->name('suppliers.export');

        Route::post('/suppliers', [\App\Http\Controllers\App\SuppliersController::class, 'store'])->name('suppliers.store');
        Route::get('/suppliers/{supplier}', [\App\Http\Controllers\App\SuppliersController::class, 'show'])->name('suppliers.show');

        Route::put('/suppliers/{supplier}', [\App\Http\Controllers\App\SuppliersController::class, 'update'])->name('suppliers.update');
        Route::delete('/suppliers/{supplier}', [\App\Http\Controllers\App\SuppliersController::class, 'destroy'])->name('suppliers.destroy');
        Route::patch('/suppliers/{supplier}/toggle', [\App\Http\Controllers\App\SuppliersController::class, 'toggleActive'])->name('suppliers.toggle');
        Route::get('/api/suppliers/search', [\App\Http\Controllers\App\SuppliersController::class, 'search'])->name('suppliers.search');
        
        // Purchase Orders
        Route::get('/orders', [\App\Http\Controllers\App\PurchaseOrdersController::class, 'index'])->name('orders.index');
        Route::get('/orders/create', [\App\Http\Controllers\App\PurchaseOrdersController::class, 'create'])->name('orders.create');
        Route::post('/orders', [\App\Http\Controllers\App\PurchaseOrdersController::class, 'store'])->name('orders.store');
        Route::get('/orders/{purchaseOrder}', [\App\Http\Controllers\App\PurchaseOrdersController::class, 'show'])->name('orders.show');
        Route::put('/orders/{purchaseOrder}', [\App\Http\Controllers\App\PurchaseOrdersController::class, 'update'])->name('orders.update');
        Route::post('/orders/{purchaseOrder}/send', [\App\Http\Controllers\App\PurchaseOrdersController::class, 'send'])->name('orders.send');
        Route::post('/orders/{purchaseOrder}/cancel', [\App\Http\Controllers\App\PurchaseOrdersController::class, 'cancel'])->name('orders.cancel');
        Route::post('/orders/{purchaseOrder}/items', [\App\Http\Controllers\App\PurchaseOrdersController::class, 'addItem'])->name('orders.items.store');
        Route::delete('/orders/{purchaseOrder}/items/{item}', [\App\Http\Controllers\App\PurchaseOrdersController::class, 'removeItem'])->name('orders.items.destroy');
        
        // Goods Received Notes
        Route::get('/orders/{purchaseOrder}/receive', [\App\Http\Controllers\App\GoodsReceivedNotesController::class, 'create'])->name('grn.create');
        Route::post('/orders/{purchaseOrder}/receive', [\App\Http\Controllers\App\GoodsReceivedNotesController::class, 'store'])->name('grn.store');
        Route::get('/grn/{goodsReceivedNote}', [\App\Http\Controllers\App\GoodsReceivedNotesController::class, 'show'])->name('grn.show');
        Route::post('/grn/{goodsReceivedNote}/post', [\App\Http\Controllers\App\GoodsReceivedNotesController::class, 'post'])->name('grn.post');
        Route::post('/grn/{goodsReceivedNote}/cancel', [\App\Http\Controllers\App\GoodsReceivedNotesController::class, 'cancel'])->name('grn.cancel');
    });

    // ───────────────────────────────────────────────────────────────
    // HR Module (Human Resources)
    // ───────────────────────────────────────────────────────────────
    Route::prefix('hr')->name('app.hr.')->group(function () {
        // Dashboard
        Route::get('/', [\App\Http\Controllers\App\HR\HRController::class, 'index'])->name('index');
        
        // Settings (employee types, job titles, allowances, deductions)
        Route::get('/settings', [\App\Http\Controllers\App\HR\SettingsController::class, 'index'])->name('settings.index');
        
        // Employee Types
        Route::post('/settings/employee-types', [\App\Http\Controllers\App\HR\SettingsController::class, 'storeEmployeeType'])->name('settings.employee-types.store');
        Route::put('/settings/employee-types/{employeeType}', [\App\Http\Controllers\App\HR\SettingsController::class, 'updateEmployeeType'])->name('settings.employee-types.update');
        Route::delete('/settings/employee-types/{employeeType}', [\App\Http\Controllers\App\HR\SettingsController::class, 'destroyEmployeeType'])->name('settings.employee-types.destroy');
        
        // Job Titles
        Route::post('/settings/job-titles', [\App\Http\Controllers\App\HR\SettingsController::class, 'storeJobTitle'])->name('settings.job-titles.store');
        Route::put('/settings/job-titles/{jobTitle}', [\App\Http\Controllers\App\HR\SettingsController::class, 'updateJobTitle'])->name('settings.job-titles.update');
        Route::delete('/settings/job-titles/{jobTitle}', [\App\Http\Controllers\App\HR\SettingsController::class, 'destroyJobTitle'])->name('settings.job-titles.destroy');
        
        // Allowances
        Route::post('/settings/allowances', [\App\Http\Controllers\App\HR\SettingsController::class, 'storeAllowance'])->name('settings.allowances.store');
        Route::put('/settings/allowances/{allowance}', [\App\Http\Controllers\App\HR\SettingsController::class, 'updateAllowance'])->name('settings.allowances.update');
        Route::delete('/settings/allowances/{allowance}', [\App\Http\Controllers\App\HR\SettingsController::class, 'destroyAllowance'])->name('settings.allowances.destroy');
        
        // Deductions
        Route::post('/settings/deductions', [\App\Http\Controllers\App\HR\SettingsController::class, 'storeDeduction'])->name('settings.deductions.store');
        Route::put('/settings/deductions/{deduction}', [\App\Http\Controllers\App\HR\SettingsController::class, 'updateDeduction'])->name('settings.deductions.update');
        Route::delete('/settings/deductions/{deduction}', [\App\Http\Controllers\App\HR\SettingsController::class, 'destroyDeduction'])->name('settings.deductions.destroy');
        
        // Employee Permissions
        Route::get('/employees/{employee}/permissions', [\App\Http\Controllers\App\HR\EmployeePermissionsController::class, 'index'])->name('employees.permissions.index');
        Route::put('/employees/{employee}/permissions', [\App\Http\Controllers\App\HR\EmployeePermissionsController::class, 'update'])->name('employees.permissions.update');

        // Employees
        Route::get('/employees/print', [\App\Http\Controllers\App\HR\EmployeeController::class, 'print'])->name('employees.print');
        Route::get('/employees', [\App\Http\Controllers\App\HR\EmployeeController::class, 'index'])->name('employees.index');
        Route::post('/employees', [\App\Http\Controllers\App\HR\EmployeeController::class, 'store'])->name('employees.store');
        Route::get('/employees/{employee}', [\App\Http\Controllers\App\HR\EmployeeController::class, 'show'])->name('employees.show');
        Route::put('/employees/{employee}', [\App\Http\Controllers\App\HR\EmployeeController::class, 'update'])->name('employees.update');
        Route::delete('/employees/{employee}', [\App\Http\Controllers\App\HR\EmployeeController::class, 'destroy'])->name('employees.destroy');
        Route::put('/employees/{employee}/allowances', [\App\Http\Controllers\App\HR\EmployeeController::class, 'updateAllowances'])->name('employees.allowances.update');
        Route::put('/employees/{employee}/deductions', [\App\Http\Controllers\App\HR\EmployeeController::class, 'updateDeductions'])->name('employees.deductions.update');
        Route::post('/employees/{employee}/upload-photo', [\App\Http\Controllers\App\HR\EmployeeController::class, 'uploadPhoto'])->name('employees.upload-photo');
        Route::put('/employees/{employee}/default-shift', [\App\Http\Controllers\App\HR\EmployeeShiftController::class, 'updateDefaultShift'])->name('employees.default-shift.update');
        Route::put('/employees/{employee}/weekly-schedule', [\App\Http\Controllers\App\HR\EmployeeShiftController::class, 'updateWeeklySchedule'])->name('employees.weekly-schedule.update');
        
        // Employee Financial
        Route::put('/employees/{employee}/bank-info', [\App\Http\Controllers\App\HR\EmployeeController::class, 'updateBankInfo'])->name('employees.bank-info.update');
        Route::put('/employees/{employee}/financial-info', [\App\Http\Controllers\App\HR\EmployeeController::class, 'updateFinancialInfo'])->name('employees.financial-info.update');
        
        
        // Payroll Runs
        Route::get('/payroll', [\App\Http\Controllers\App\HR\PayrollController::class, 'index'])->name('payroll.index');
        Route::post('/payroll/generate', [\App\Http\Controllers\App\HR\PayrollController::class, 'generate'])->name('payroll.generate');
        
        // Other Payments (MUST be before {payrollRun} wildcard routes!)
        Route::get('/payroll/other-payments', [\App\Http\Controllers\App\HR\OtherPaymentsController::class, 'index'])->name('payroll.other-payments.index');
        Route::post('/payroll/other-payments', [\App\Http\Controllers\App\HR\OtherPaymentsController::class, 'store'])->name('payroll.other-payments.store');
        Route::put('/payroll/other-payments/{otherPayment}', [\App\Http\Controllers\App\HR\OtherPaymentsController::class, 'update'])->name('payroll.other-payments.update');
        Route::delete('/payroll/other-payments/{otherPayment}', [\App\Http\Controllers\App\HR\OtherPaymentsController::class, 'destroy'])->name('payroll.other-payments.destroy');
        Route::put('/payroll/other-payments/{otherPayment}/approve', [\App\Http\Controllers\App\HR\OtherPaymentsController::class, 'approve'])->name('payroll.other-payments.approve');
        Route::put('/payroll/other-payments/{otherPayment}/pay', [\App\Http\Controllers\App\HR\OtherPaymentsController::class, 'markAsPaid'])->name('payroll.other-payments.pay');

        // Payroll Run Details (wildcard routes)
        Route::get('/payroll/{payrollRun}', [\App\Http\Controllers\App\HR\PayrollController::class, 'show'])->name('payroll.show');
        Route::put('/payroll/{payrollRun}/approve', [\App\Http\Controllers\App\HR\PayrollController::class, 'approve'])->name('payroll.approve');
        Route::put('/payroll/{payrollRun}/regenerate', [\App\Http\Controllers\App\HR\PayrollController::class, 'regenerate'])->name('payroll.regenerate');
        Route::put('/payroll/{payrollRun}/mark-paid', [\App\Http\Controllers\App\HR\PayrollController::class, 'markPaid'])->name('payroll.mark-paid');
        Route::delete('/payroll/{payrollRun}/items/{payrollItem}', [\App\Http\Controllers\App\HR\PayrollController::class, 'destroyItem'])->name('payroll.items.destroy');
        Route::get('/payroll/{payrollRun}/print', [\App\Http\Controllers\App\HR\PayrollController::class, 'print'])->name('payroll.print');

        // Employee Payroll
        Route::get('/employees/{employee}/payroll', [\App\Http\Controllers\App\HR\PayrollController::class, 'employeePayroll'])->name('employees.payroll');
        Route::get('/payroll-items/{payrollItem}/print', [\App\Http\Controllers\App\HR\PayrollController::class, 'printPaySlip'])->name('payroll.payslip');

        // Attendance
        Route::get('/attendance/print', [\App\Http\Controllers\App\HR\AttendanceController::class, 'print'])->name('attendance.print');
        Route::get('/attendance', [\App\Http\Controllers\App\HR\AttendanceController::class, 'index'])->name('attendance.index');
        Route::post('/attendance', [\App\Http\Controllers\App\HR\AttendanceController::class, 'store'])->name('attendance.store');
        Route::put('/attendance/{attendance}', [\App\Http\Controllers\App\HR\AttendanceController::class, 'update'])->name('attendance.update');
        Route::delete('/attendance/{attendance}', [\App\Http\Controllers\App\HR\AttendanceController::class, 'destroy'])->name('attendance.destroy');

        // Leaves
        Route::post('/leaves', [\App\Http\Controllers\App\HR\LeaveController::class, 'store'])->name('leaves.store');
        Route::put('/leaves/{leave}', [\App\Http\Controllers\App\HR\LeaveController::class, 'update'])->name('leaves.update');
        Route::delete('/leaves/{leave}', [\App\Http\Controllers\App\HR\LeaveController::class, 'destroy'])->name('leaves.destroy');
        Route::put('/leaves/{leave}/status', [\App\Http\Controllers\App\HR\LeaveController::class, 'updateStatus'])->name('leaves.update-status');

        // Biometric Devices
        Route::post('/settings/biometric-devices', [\App\Http\Controllers\App\HR\BiometricDeviceController::class, 'store'])->name('settings.biometric-devices.store');
        Route::put('/settings/biometric-devices/{biometricDevice}', [\App\Http\Controllers\App\HR\BiometricDeviceController::class, 'update'])->name('settings.biometric-devices.update');
        Route::delete('/settings/biometric-devices/{biometricDevice}', [\App\Http\Controllers\App\HR\BiometricDeviceController::class, 'destroy'])->name('settings.biometric-devices.destroy');
        Route::post('/settings/biometric-devices/{biometricDevice}/regenerate-token', [\App\Http\Controllers\App\HR\BiometricDeviceController::class, 'regenerateToken'])->name('settings.biometric-devices.regenerate-token');
        Route::get('/settings/biometric-devices/{biometricDevice}/token', [\App\Http\Controllers\App\HR\BiometricDeviceController::class, 'showToken'])->name('settings.biometric-devices.show-token');

        // Shifts
        Route::post('/settings/shifts', [\App\Http\Controllers\App\HR\ShiftController::class, 'store'])->name('settings.shifts.store');
        Route::put('/settings/shifts/{shift}', [\App\Http\Controllers\App\HR\ShiftController::class, 'update'])->name('settings.shifts.update');
        Route::delete('/settings/shifts/{shift}', [\App\Http\Controllers\App\HR\ShiftController::class, 'destroy'])->name('settings.shifts.destroy');

        // Attendance Settings
        Route::put('/settings/attendance', [\App\Http\Controllers\App\HR\SettingsController::class, 'updateAttendanceSettings'])->name('settings.attendance.update');
    });

    // ───────────────────────────────────────────────────────────────
    // Invoices & Payments
    // ───────────────────────────────────────────────────────────────
    Route::prefix('invoices')->name('app.invoices.')->group(function () {
        Route::get('/', [\App\Http\Controllers\App\InvoicesController::class, 'index'])->name('index');
        Route::get('/{invoice}', [\App\Http\Controllers\App\InvoicesController::class, 'show'])->name('show');
        Route::get('/{invoice}/print', [\App\Http\Controllers\App\InvoicesController::class, 'print'])->name('print');
        
        // Payments on Invoice
        Route::post('/{invoice}/payments', [\App\Http\Controllers\App\PaymentsController::class, 'store'])->name('payments.store');
        Route::post('/{invoice}/pay-full', [\App\Http\Controllers\App\PaymentsController::class, 'payFull'])->name('payments.pay-full');
    });
    
    // Delete payment (outside invoices prefix)
    Route::delete('/payments/{payment}', [\App\Http\Controllers\App\PaymentsController::class, 'destroy'])->name('app.payments.destroy');

    // Generate invoice from work order
    Route::post('/work-orders/{workOrder}/invoice', [\App\Http\Controllers\App\InvoicesController::class, 'createFromWorkOrder'])->name('app.work-orders.invoice');
    Route::get('/work-orders/{workOrder}/proforma', [\App\Http\Controllers\App\InvoicesController::class, 'printProforma'])->name('app.work-orders.proforma');
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
