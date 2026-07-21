<?php

declare(strict_types=1);

use App\Http\Controllers\App\BranchesController;
use App\Http\Controllers\App\CenterSettingsController;
use App\Http\Controllers\App\CompanyProfileController;
use App\Http\Controllers\App\CompanyTransactionController;
use App\Http\Controllers\App\CustomerController;
use App\Http\Controllers\App\CustomerImportExportController;
use App\Http\Controllers\App\CustomerMergeController;
use App\Http\Controllers\App\DashboardController;
use App\Http\Controllers\App\DepartmentController;
use App\Http\Controllers\App\EmployeePortalController;
use App\Http\Controllers\App\GoodsReceivedNotesController;
use App\Http\Controllers\App\HR\AttendanceController;
use App\Http\Controllers\App\HR\BiometricDeviceController;
use App\Http\Controllers\App\HR\EmployeeContractsController;
use App\Http\Controllers\App\HR\EmployeeController;
use App\Http\Controllers\App\HR\EmployeeDocumentsController;
use App\Http\Controllers\App\HR\EmployeeFinancialsController;
use App\Http\Controllers\App\HR\EmployeePermissionsController;
use App\Http\Controllers\App\HR\EmployeeShiftController;
use App\Http\Controllers\App\HR\HRController;
use App\Http\Controllers\App\HR\HRPayrollSettingsController;
use App\Http\Controllers\App\HR\HRRegulationsController;
use App\Http\Controllers\App\HR\LeaveController;
use App\Http\Controllers\App\HR\OtherPaymentsController;
use App\Http\Controllers\App\HR\PayrollController;
use App\Http\Controllers\App\HR\ShiftController;
use App\Http\Controllers\App\IncomeCategoryController;
use App\Http\Controllers\App\InventoryBalanceController;
use App\Http\Controllers\App\InventoryMoveController;
use App\Http\Controllers\App\InventorySettingsController;
use App\Http\Controllers\App\InventoryTransfersController;
use App\Http\Controllers\App\InvoicesController;
use App\Http\Controllers\App\NotificationController;
use App\Http\Controllers\App\PartsController;
use App\Http\Controllers\App\PaymentsController;
use App\Http\Controllers\App\PrintSettings\Signatures\DestroySignatureController;
use App\Http\Controllers\App\PrintSettings\Signatures\IndexSignaturesController;
use App\Http\Controllers\App\PrintSettings\Signatures\ReorderSignaturesController;
use App\Http\Controllers\App\PrintSettings\Signatures\StoreSignatureController;
use App\Http\Controllers\App\PrintSettings\Signatures\UpdateSignatureController;
use App\Http\Controllers\App\PurchaseInvoicesController;
use App\Http\Controllers\App\PurchaseOrdersController;
use App\Http\Controllers\App\PurchaseReturnsController;
use App\Http\Controllers\App\PurchasingHubController;
use App\Http\Controllers\App\PurchasingInvoicesController;
use App\Http\Controllers\App\QuoteApprovalController;
use App\Http\Controllers\App\Quotes\QuoteController;
use App\Http\Controllers\App\Quotes\QuotePartsController;
use App\Http\Controllers\App\Quotes\QuotePrintController;
use App\Http\Controllers\App\Quotes\QuoteServiceController;
use App\Http\Controllers\App\ReportsController;
use App\Http\Controllers\App\RoleController;
use App\Http\Controllers\App\ServiceController;
use App\Http\Controllers\App\SettingsController;
use App\Http\Controllers\App\SuppliersController;
use App\Http\Controllers\App\SystemSettingsController;
use App\Http\Controllers\App\TwoFactorAuthenticatedSessionController;
use App\Http\Controllers\App\TwoFactorController;
use App\Http\Controllers\App\UserController;
use App\Http\Controllers\App\VehicleColorController;
use App\Http\Controllers\App\VehicleConditionCategoryController;
use App\Http\Controllers\App\VehicleConditionItemController;
use App\Http\Controllers\App\VehicleController;
use App\Http\Controllers\App\VehicleMakeController;
use App\Http\Controllers\App\VehicleMileageController;
use App\Http\Controllers\App\VehicleModelController;
use App\Http\Controllers\App\WorkOrderInspectionController;
use App\Http\Controllers\App\WorkOrders\WorkOrderController;
use App\Http\Controllers\App\WorkOrders\WorkOrderItemController;
use App\Http\Controllers\App\WorkOrders\WorkOrderMediaController;
use App\Http\Controllers\App\WorkOrders\WorkOrderNotesController;
use App\Http\Controllers\App\WorkOrders\WorkOrderPartsController;
use App\Http\Controllers\App\WorkOrders\WorkOrderPaymentController;
use App\Http\Controllers\App\WorkOrders\WorkOrderPrintController;
use App\Http\Controllers\App\WorkOrders\WorkOrderStatusController;
use App\Http\Controllers\App\WorkOrders\WorkOrderTechnicianController;
use App\Http\Controllers\App\WorkOrders\WorkOrderWarrantiesController;
use App\Http\Controllers\App\WorkOrderSignatureController;
use App\Http\Controllers\Auth\PhoneVerificationController;
use App\Http\Controllers\Auth\SetPasswordController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Public\PublicLandingController;
use App\Http\Controllers\Public\PublicQuoteController;
use App\Http\Controllers\System\AdminUsersController;
use App\Http\Controllers\System\AnnouncementsController;
use App\Http\Controllers\System\CommunicationTemplatesController;
use App\Http\Controllers\System\ContactMessageController;
use App\Http\Controllers\System\DeveloperController;
use App\Http\Controllers\System\GeneralSettingsController;
use App\Http\Controllers\System\ImpersonationController;
use App\Http\Controllers\System\InstallmentsController;
use App\Http\Controllers\System\IntegrationsController;
use App\Http\Controllers\System\PaymentController;
use App\Http\Controllers\System\PaymentSettingsController;
use App\Http\Controllers\System\PlansController;
use App\Http\Controllers\System\PromoCodesController;
use App\Http\Controllers\System\SmsCreditsController;
use App\Http\Controllers\System\SubscriptionInvoicesController;
use App\Http\Controllers\System\SubscriptionsController;
use App\Http\Controllers\System\SystemDashboardController;
use App\Http\Controllers\System\TenantsController;
use App\Http\Controllers\System\TenantSecurityController;
use App\Http\Controllers\System\WebsiteSettingsController;
use App\Http\Controllers\System\WhatsappCreditsController;
use App\Http\Middleware\EnsureTenantActive;
use App\Http\Middleware\EnsureTwoFactorEnabled;
use Illuminate\Support\Facades\Route;

// ─────────────────────────────────────────────────────────────────────────────
// Public Routes (No Authentication Required)
// ─────────────────────────────────────────────────────────────────────────────
Route::prefix('view')->name('public.')->group(function () {
    Route::get('/quote/{uuid}', [PublicQuoteController::class, 'show'])->name('quotes.show');
    Route::post('/quote/{uuid}/approve', [PublicQuoteController::class, 'approve'])->middleware('throttle:quote-public')->name('quotes.approve');
    Route::post('/quote/{uuid}/reject', [PublicQuoteController::class, 'reject'])->middleware('throttle:quote-public')->name('quotes.reject');
});

Route::get('/', [PublicLandingController::class, 'preview'])->name('home');

Route::get('/landing-preview', [PublicLandingController::class, 'preview'])->name('public.landing.preview');
Route::post('/landing-preview/contact', [PublicLandingController::class, 'submitContact'])->middleware('throttle:public-landing')->name('public.landing.contact');

// Phone Verification (Registration)
Route::post('/phone/send-otp', [PhoneVerificationController::class, 'sendOtp'])->middleware('throttle:phone-otp')->name('phone.send-otp');
Route::post('/phone/verify-otp', [PhoneVerificationController::class, 'verifyOtp'])->middleware('throttle:phone-otp')->name('phone.verify-otp');

// Locale Management
Route::post('/locale', [LocaleController::class, 'setLocale'])->name('locale.set');
Route::get('/locale', [LocaleController::class, 'getLocale'])->name('locale.get');

// Invitation Routes
Route::middleware('guest')->group(function () {
    Route::get('invitations/accept/{user}', [SetPasswordController::class, 'show'])->name('invitations.accept');
    Route::post('invitations/accept/{user}', [SetPasswordController::class, 'store']);
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'tenant.active', 'center.context'])
    ->name('dashboard');

// 2FA Challenge (during login)
Route::get('/app/2fa/challenge', [TwoFactorAuthenticatedSessionController::class, 'challenge'])->name('app.2fa.challenge');
Route::post('/app/2fa/verify', [TwoFactorAuthenticatedSessionController::class, 'verify'])->middleware('throttle:2fa-verify')->name('app.2fa.verify');
Route::post('/app/2fa/challenge/resend', [TwoFactorAuthenticatedSessionController::class, 'resend'])->middleware('throttle:phone-otp')->name('app.2fa.challenge.resend');

Route::middleware(['auth', 'verified', EnsureTenantActive::class])->prefix('app')->group(function () {
    // Profile
    Route::get('/profile', [App\Http\Controllers\App\ProfileController::class, 'index'])->name('app.profile');
    Route::patch('/profile', [App\Http\Controllers\App\ProfileController::class, 'update'])->name('app.profile.update');
    Route::post('/profile/photo', [App\Http\Controllers\App\ProfileController::class, 'updatePhoto'])->middleware('throttle:uploads')->name('app.profile.photo.update');
    Route::post('/profile/switch-center', [ProfileController::class, 'switchCenter'])->name('profile.switch-center');

    // Two-Factor Authentication (Tenant App)
    Route::post('/security/2fa/send-code', [TwoFactorController::class, 'sendCode'])->middleware('throttle:phone-otp')->name('app.2fa.send-code');
    Route::post('/security/2fa/enable', [TwoFactorController::class, 'enable'])->middleware('throttle:2fa-verify')->name('app.security.2fa.enable');
    Route::post('/security/2fa/disable', [TwoFactorController::class, 'disable'])->name('app.security.2fa.disable');
    Route::post('/security/2fa/regenerate', [TwoFactorController::class, 'regenerateRecoveryCodes'])->name('app.security.2fa.regenerate');

    // Employee Portal (Self-Service) - requires 'employee' role
    Route::prefix('my')->middleware('role:employee')->group(function () {
        Route::get('/', [EmployeePortalController::class, 'dashboard'])->name('employee.dashboard');
        Route::get('/profile', [EmployeePortalController::class, 'profile'])->name('employee.profile');
        Route::get('/attendance', [EmployeePortalController::class, 'attendance'])->name('employee.attendance');
        Route::get('/leaves', [EmployeePortalController::class, 'leaves'])->name('employee.leaves');
        Route::post('/leaves', [EmployeePortalController::class, 'requestLeave'])->name('employee.leaves.request');
        Route::get('/payslips', [EmployeePortalController::class, 'payslips'])->name('employee.payslips');
        Route::get('/payslips/{payslip}', [EmployeePortalController::class, 'showPayslip'])->name('employee.payslips.show');
    });
});

// App routes (authenticated + tenancy)
Route::prefix('app')->middleware(['auth', 'verified', 'tenant.active', 'center.context', EnsureTwoFactorEnabled::class])->group(function () {
    // Customer export/import routes (must be before resource routes)
    Route::get('/customers/export', [CustomerImportExportController::class, 'export'])->name('customers.export');
    Route::get('/customers/print', [CustomerController::class, 'print'])->name('customers.print');
    Route::get('/customers/import/template', [CustomerImportExportController::class, 'downloadTemplate'])->name('customers.import.template');
    Route::post('/customers/import', [CustomerImportExportController::class, 'import'])->name('customers.import');

    Route::get('/customers/check-phone', [CustomerController::class, 'checkPhone'])->name('customers.check-phone');
    Route::resource('customers', CustomerController::class);
    // Customer merge routes
    Route::get('/customers/{customer}/merge', [CustomerMergeController::class, 'mergeData'])->name('customers.merge-data');
    Route::post('/customers/{source}/merge/{target}', [CustomerMergeController::class, 'executeMerge'])->name('customers.execute-merge');

    // Vehicle export/print routes
    Route::get('/vehicles/export', [VehicleController::class, 'export'])->name('vehicles.export');
    Route::get('/vehicles/print', [VehicleController::class, 'print'])->name('vehicles.print');
    Route::get('/vehicles/check-plate', [VehicleController::class, 'checkPlate'])->name('vehicles.check-plate');
    Route::apiResource('vehicles', VehicleController::class);
    Route::get('/vehicles/{vehicle}/mileage-logs', [VehicleMileageController::class, 'index'])->name('vehicles.mileage-logs.index');
    Route::delete('/vehicles/{vehicle}/mileage-logs/{log}', [VehicleMileageController::class, 'destroy'])->name('vehicles.mileage-logs.destroy');

    // Work Orders - Hub and Index
    Route::get('/work-orders/export', [WorkOrderController::class, 'export'])->name('work-orders.export');
    Route::get('/work-orders', [WorkOrderController::class, 'index'])->name('work-orders.index');
    Route::post('/work-orders', [WorkOrderController::class, 'store'])->name('work-orders.store');
    Route::get('/work-orders/{workOrder}', [WorkOrderController::class, 'show'])->name('work-orders.show');
    Route::put('/work-orders/{workOrder}', [WorkOrderController::class, 'update'])->name('work-orders.update');
    Route::delete('/work-orders/{workOrder}', [WorkOrderController::class, 'destroy'])->name('work-orders.destroy');

    // Work Order Items (Services)
    Route::post('/work-orders/{work_order}/items', [WorkOrderItemController::class, 'addItem'])->name('work-orders.items.store');
    Route::put('/work-orders/{work_order}/items/{item}', [WorkOrderItemController::class, 'updateItem'])->name('work-orders.items.update');
    Route::delete('/work-orders/{work_order}/items/{item}', [WorkOrderItemController::class, 'deleteItem'])->name('work-orders.items.destroy');

    // Work Order Departments
    Route::post('/work-orders/{work_order}/departments', [WorkOrderItemController::class, 'addDepartment'])->name('work-orders.departments.store');
    Route::delete('/work-orders/{work_order}/departments/{department_id}', [WorkOrderItemController::class, 'removeDepartment'])->name('work-orders.departments.destroy');

    // Work Order Parts (Inventory Integration)
    Route::post('/work-orders/{workOrder}/parts', [App\Http\Controllers\App\WorkOrderPartsController::class, 'store'])->name('work-orders.parts.store');
    Route::put('/work-order-parts/{workOrderPart}', [App\Http\Controllers\App\WorkOrderPartsController::class, 'update'])->name('work-orders.parts.update');
    Route::delete('/work-order-parts/{workOrderPart}', [App\Http\Controllers\App\WorkOrderPartsController::class, 'destroy'])->name('work-orders.parts.destroy');
    Route::post('/work-order-parts/{workOrderPart}/reverse', [App\Http\Controllers\App\WorkOrderPartsController::class, 'reverse'])->name('work-orders.parts.reverse');
    Route::get('/api/work-order-parts/check-stock', [App\Http\Controllers\App\WorkOrderPartsController::class, 'checkStock'])->name('work-orders.parts.check-stock');
    Route::get('/api/vehicles/{vehicle_id}/active-warranties', [WorkOrderWarrantiesController::class, 'activeWarranties'])->name('vehicles.active-warranties');

    // Work Order Status Management
    Route::post('/work-orders/{work_order}/start', [WorkOrderStatusController::class, 'startWork'])->name('work-orders.start');
    Route::post('/work-orders/{work_order}/hold', [WorkOrderStatusController::class, 'putOnHold'])->name('work-orders.hold');
    Route::post('/work-orders/{work_order}/resume', [WorkOrderStatusController::class, 'resume'])->name('work-orders.resume');
    Route::post('/work-orders/{work_order}/cancel', [WorkOrderStatusController::class, 'cancel'])->name('work-orders.cancel');
    Route::post('/work-orders/{work_order}/complete', [WorkOrderStatusController::class, 'complete'])->name('work-orders.complete');

    // Work Order Inspections
    Route::get('/work-orders/{workOrder}/inspections/templates', [WorkOrderInspectionController::class, 'getTemplates'])->name('work-orders.inspections.templates');
    Route::post('/work-orders/{workOrder}/inspections', [WorkOrderInspectionController::class, 'store'])->name('work-orders.inspections.store');
    Route::get('/work-orders/{workOrder}/inspections/{inspection}', [WorkOrderInspectionController::class, 'show'])->name('work-orders.inspections.show');

    // Work Order Signatures
    Route::post('/work-orders/{workOrder}/signatures', [WorkOrderSignatureController::class, 'store'])->name('work-orders.signatures.store');

    // Work Order Print Routes
    Route::get('/work-orders/{workOrder}/print/condition', [WorkOrderPrintController::class, 'printCondition'])->name('work-orders.print.condition');
    Route::get('/work-orders/{workOrder}/print/services', [WorkOrderPrintController::class, 'printServices'])->name('work-orders.print.services');
    Route::get('/work-orders/{workOrder}/print/proforma', [WorkOrderPrintController::class, 'printProforma'])->name('work-orders.print.proforma');
    Route::get('/work-orders/{workOrder}/print/payments', [WorkOrderPrintController::class, 'printPayments'])->name('work-orders.print.payments');

    // Work Order Payments
    Route::post('/work-orders/{workOrder}/payments', [WorkOrderPaymentController::class, 'storePayment'])->name('work-orders.payments.store');
    Route::put('/work-orders/{workOrder}/payments/{payment}', [WorkOrderPaymentController::class, 'updatePayment'])->name('work-orders.payments.update');
    Route::delete('/work-orders/{workOrder}/payments/{payment}', [WorkOrderPaymentController::class, 'destroyPayment'])->name('work-orders.payments.destroy');

    // Work Order Condition Report (Fuel Level & Damage Marks)
    Route::put('/work-orders/{workOrder}/condition', [WorkOrderStatusController::class, 'updateCondition'])->name('app.work-orders.update-condition');

    // Work Order Item Status
    Route::patch('/work-orders/{work_order}/items/{item}/status', [WorkOrderTechnicianController::class, 'updateItemStatus'])->name('work-orders.items.status');

    // Work Order Item Technicians
    Route::post('/work-orders/{work_order}/items/{item}/technicians', [WorkOrderTechnicianController::class, 'assignTechnician'])->name('work-orders.items.technicians.store');
    Route::delete('/work-orders/{work_order}/items/{item}/technicians/{user}', [WorkOrderTechnicianController::class, 'removeTechnician'])->name('work-orders.items.technicians.destroy');

    // Work Order Item Parts
    Route::post('/work-orders/{work_order}/items/{item}/parts', [WorkOrderPartsController::class, 'addPart'])->name('work-orders.items.parts.store');
    Route::put('/work-orders/{work_order}/items/{item}/parts/{part}', [WorkOrderPartsController::class, 'updatePart'])->name('work-orders.items.parts.update');
    Route::delete('/work-orders/{work_order}/items/{item}/parts/{part}', [WorkOrderPartsController::class, 'deletePart'])->name('work-orders.items.parts.destroy');

    // Work Order Item Notes
    Route::post('/work-orders/{work_order}/items/{item}/notes', [WorkOrderNotesController::class, 'addNote'])->name('work-orders.items.notes.store');
    Route::delete('/work-orders/{work_order}/items/{item}/notes/{note}', [WorkOrderNotesController::class, 'deleteNote'])->name('work-orders.items.notes.destroy');

    // Work Order General Notes
    Route::post('/work-orders/{work_order}/notes', [WorkOrderNotesController::class, 'addGeneralNote'])->name('work-orders.notes.store');
    Route::delete('/work-orders/{work_order}/notes/{note}', [WorkOrderNotesController::class, 'deleteGeneralNote'])->name('work-orders.notes.destroy');

    // Work Order Photos
    Route::post('/work-orders/{workOrder}/photos', [WorkOrderMediaController::class, 'uploadPhotos'])->name('work-orders.photos.store');
    Route::delete('/work-orders/{workOrder}/photos/{photo}', [WorkOrderMediaController::class, 'deletePhoto'])->name('work-orders.photos.destroy');

    Route::post('/work-orders/{workOrder}/attachments', [WorkOrderMediaController::class, 'uploadAttachments'])->name('work-orders.attachments.store');
    Route::delete('/work-orders/{workOrder}/attachments/{attachment}', [WorkOrderMediaController::class, 'destroyAttachment'])->name('work-orders.attachments.destroy');

    // Quotes
    Route::get('/quotes', [QuoteController::class, 'index'])->name('app.quotes.index');
    Route::post('/quotes', [QuoteController::class, 'store'])->name('app.quotes.store');
    Route::put('/quotes/{quote}', [QuoteController::class, 'update'])->name('app.quotes.update');
    Route::delete('/quotes/{quote}', [QuoteController::class, 'destroy'])->name('app.quotes.destroy');
    Route::post('/quotes/{quote}/approve', [QuoteApprovalController::class, 'approve'])->name('app.quotes.approve');
    Route::post('/quotes/{quote}/reject', [QuoteApprovalController::class, 'reject'])->name('app.quotes.reject');
    Route::get('/quotes/search', [QuoteController::class, 'search'])->name('app.quotes.search');
    Route::get('/quotes/{quote}', [QuoteController::class, 'show'])->name('app.quotes.show');
    Route::get('/quotes/{quote}/print', [QuotePrintController::class, 'print'])->name('app.quotes.print');
    Route::post('/quotes/{quote}/services', [QuoteServiceController::class, 'addService'])->name('app.quotes.services.store');
    Route::put('/quotes/{quote}/services/{line}', [QuoteServiceController::class, 'updateService'])->name('app.quotes.services.update');
    Route::delete('/quotes/{quote}/services/{line}', [QuoteServiceController::class, 'deleteService'])->name('app.quotes.services.destroy');
    Route::post('/quotes/{quote}/departments', [QuoteServiceController::class, 'addDepartment'])->name('app.quotes.departments.store');
    Route::delete('/quotes/{quote}/departments/{department_id}', [QuoteServiceController::class, 'removeDepartment'])->name('app.quotes.departments.destroy');

    // Quote Parts
    Route::post('/quotes/{quote}/parts', [QuotePartsController::class, 'addPart'])->name('app.quotes.parts.store');
    Route::put('/quotes/{quote}/parts/{quotePart}', [QuotePartsController::class, 'updatePart'])->name('app.quotes.parts.update');
    Route::delete('/quotes/{quote}/parts/{quotePart}', [QuotePartsController::class, 'deletePart'])->name('app.quotes.parts.destroy');

    // Tenants
    Route::resource('tenants', TenantsController::class);

    // Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::get('/settings/integrations', [SettingsController::class, 'integrations'])->name('settings.integrations');
    Route::get('/settings/website', [SettingsController::class, 'website'])->name('settings.website');

    // Company Profile Settings
    Route::get('/settings/company', [CompanyProfileController::class, 'index'])->name('settings.company');
    Route::put('/settings/company', [CompanyProfileController::class, 'update'])->name('settings.company.update');
    Route::put('/settings/company/admin-user', [CompanyProfileController::class, 'updateAdminUser'])->name('settings.company.admin-user');
    Route::post('/settings/company/verify-password', [CompanyProfileController::class, 'verifyPassword'])->name('settings.company.verify-password');
    Route::post('/settings/company/logo', [CompanyProfileController::class, 'uploadLogo'])->name('settings.company.logo.upload');
    Route::delete('/settings/company/logo', [CompanyProfileController::class, 'deleteLogo'])->name('settings.company.logo.delete');

    // Company Transactions Settings
    Route::get('/settings/company/contacts/search', [CompanyTransactionController::class, 'searchContacts'])->name('settings.company.contacts.search');
    Route::resource('/settings/company/transactions', CompanyTransactionController::class)->names('settings.company.transactions');
    Route::post('/settings/company/transactions/{transaction}/approve', [CompanyTransactionController::class, 'approve'])->name('settings.company.transactions.approve');

    // Branches Settings
    Route::get('/settings/branches', [BranchesController::class, 'index'])->name('settings.branches');
    Route::post('/settings/branches', [BranchesController::class, 'store'])->name('settings.branches.store');

    // Center Settings
    Route::get('/settings/centers/{center}', [CenterSettingsController::class, 'index'])->name('settings.centers.show');
    Route::put('/settings/centers/{center}', [CenterSettingsController::class, 'update'])->name('settings.centers.update');
    Route::post('/settings/centers/{center}/logo', [CenterSettingsController::class, 'uploadLogo'])->name('settings.centers.logo.upload');
    Route::delete('/settings/centers/{center}/logo', [CenterSettingsController::class, 'deleteLogo'])->name('settings.centers.logo.delete');
    Route::post('/settings/centers/{center}/stamp', [CenterSettingsController::class, 'uploadStamp'])->name('settings.centers.stamp.upload');
    Route::delete('/settings/centers/{center}/stamp', [CenterSettingsController::class, 'deleteStamp'])->name('settings.centers.stamp.delete');

    // System Settings
    Route::get('/settings/system', [SystemSettingsController::class, 'index'])->name('settings.system');
    Route::get('/settings/print', [SystemSettingsController::class, 'printSettings'])->name('settings.print');
    Route::put('/settings/system', [SystemSettingsController::class, 'update'])->name('settings.system.update');

    // Print Settings — Signatures (tenant-scoped image uploads + lifecycle)
    // Each endpoint is a single-action controller under
    // App\Http\Controllers\App\PrintSettings\Signatures. This keeps
    // each class under ~150 lines and makes the route => handler
    // mapping obvious without a 600+ line god controller.
    Route::get('/settings/print/signatures', IndexSignaturesController::class)->name('settings.print.signatures.index');
    Route::post('/settings/print/signatures', StoreSignatureController::class)->middleware('throttle:uploads')->name('settings.print.signatures.store');
    Route::patch('/settings/print/signatures/{signatureId}', UpdateSignatureController::class)->where('signatureId', '[0-9a-f-]{36}')->name('settings.print.signatures.update');
    Route::delete('/settings/print/signatures/{signatureId}', DestroySignatureController::class)->where('signatureId', '[0-9a-f-]{36}')->name('settings.print.signatures.destroy');
    Route::post('/settings/print/signatures/reorder', ReorderSignaturesController::class)->name('settings.print.signatures.reorder');

    // Users Settings
    Route::resource('settings/users', UserController::class)->names('settings.users');
    Route::patch('/settings/users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('settings.users.toggle');

    // Roles Settings
    Route::resource('settings/roles', RoleController::class)->names('settings.roles');

    // Vehicle Makes
    Route::get('/settings/makes', [VehicleMakeController::class, 'index'])->name('settings.makes.index');
    Route::post('/settings/makes', [VehicleMakeController::class, 'store'])->name('settings.makes.store');
    Route::put('/settings/makes/{make}', [VehicleMakeController::class, 'update'])->name('settings.makes.update');
    Route::delete('/settings/makes/{make}', [VehicleMakeController::class, 'destroy'])->name('settings.makes.destroy');
    Route::patch('/settings/makes/{make}/toggle-active', [VehicleMakeController::class, 'toggleActive'])->name('settings.makes.toggle');

    // Vehicle Models
    Route::get('/settings/models', [VehicleModelController::class, 'index'])->name('settings.models.index');
    Route::post('/settings/models', [VehicleModelController::class, 'store'])->name('settings.models.store');
    Route::put('/settings/models/{model}', [VehicleModelController::class, 'update'])->name('settings.models.update');
    Route::delete('/settings/models/{model}', [VehicleModelController::class, 'destroy'])->name('settings.models.destroy');
    Route::patch('/settings/models/{model}/toggle-active', [VehicleModelController::class, 'toggleActive'])->name('settings.models.toggle');

    // Vehicle Colors
    Route::get('/settings/colors', [VehicleColorController::class, 'index'])->name('settings.colors.index');
    Route::post('/settings/colors', [VehicleColorController::class, 'store'])->name('settings.colors.store');
    Route::put('/settings/colors/{color}', [VehicleColorController::class, 'update'])->name('settings.colors.update');
    Route::delete('/settings/colors/{color}', [VehicleColorController::class, 'destroy'])->name('settings.colors.destroy');
    Route::patch('/settings/colors/{color}/toggle-active', [VehicleColorController::class, 'toggleActive'])->name('settings.colors.toggle');

    // Income Categories
    Route::get('/settings/income-categories', [IncomeCategoryController::class, 'index'])->name('settings.income-categories.index');
    Route::post('/settings/income-categories', [IncomeCategoryController::class, 'store'])->name('settings.income-categories.store');
    Route::put('/settings/income-categories/{incomeCategory}', [IncomeCategoryController::class, 'update'])->name('settings.income-categories.update');
    Route::delete('/settings/income-categories/{incomeCategory}', [IncomeCategoryController::class, 'destroy'])->name('settings.income-categories.destroy');
    Route::patch('/settings/income-categories/{incomeCategory}/toggle-active', [IncomeCategoryController::class, 'toggleActive'])->name('settings.income-categories.toggle');

    // Vehicle Condition Items
    Route::get('/settings/condition-items', [VehicleConditionItemController::class, 'index'])->name('app.condition-items.index');
    Route::post('/settings/condition-items', [VehicleConditionItemController::class, 'store'])->name('app.condition-items.store');
    Route::put('/settings/condition-items/{conditionItem}', [VehicleConditionItemController::class, 'update'])->name('app.condition-items.update');
    Route::delete('/settings/condition-items/{conditionItem}', [VehicleConditionItemController::class, 'destroy'])->name('app.condition-items.destroy');
    Route::patch('/settings/condition-items/{conditionItem}/toggle-active', [VehicleConditionItemController::class, 'toggleActive'])->name('app.condition-items.toggle');

    Route::post('/settings/condition-categories', [VehicleConditionCategoryController::class, 'store'])->name('app.condition-categories.store');
    Route::put('/settings/condition-categories/{conditionCategory}', [VehicleConditionCategoryController::class, 'update'])->name('app.condition-categories.update');
    Route::delete('/settings/condition-categories/{conditionCategory}', [VehicleConditionCategoryController::class, 'destroy'])->name('app.condition-categories.destroy');
    Route::patch('/settings/condition-categories/{conditionCategory}/toggle-active', [VehicleConditionCategoryController::class, 'toggleActive'])->name('app.condition-categories.toggle');

    // Departments
    Route::apiResource('departments', DepartmentController::class);
    Route::patch('/departments/{department}/toggle-active', [DepartmentController::class, 'toggleActive']);

    // Services
    Route::apiResource('services', ServiceController::class);
    Route::patch('/services/{service}/toggle-active', [ServiceController::class, 'toggleActive']);
    Route::post('/services/toggle-inspections-setting', [ServiceController::class, 'toggleInspectionsSetting'])->name('app.services.toggle-inspections-setting');

    // API endpoints (Refactored to separate Controller)
    Route::get('/api/customers', [CustomerController::class, 'apiIndex']);
    Route::get('/api/vehicles-index', [VehicleController::class, 'apiIndex'])->name('vehicles.api.index');
    Route::get('/api/quotes-index', [QuoteController::class, 'apiIndex'])->name('quotes.api.index');
    Route::get('/api/work-orders-index', [WorkOrderController::class, 'apiIndex'])->name('work-orders.api.index');
    Route::get('/api/customers/search', [App\Http\Controllers\Api\WorkOrderController::class, 'customerSearch']);
    Route::get('/api/vehicles', [App\Http\Controllers\Api\WorkOrderController::class, 'customerVehicles']);
    Route::get('/api/vehicles/search', [App\Http\Controllers\Api\WorkOrderController::class, 'vehicleSearch']);
    Route::get('/api/work-orders', [App\Http\Controllers\Api\WorkOrderController::class, 'index']);
    Route::get('/api/services', [ServiceController::class, 'apiList']);
    Route::get('/api/makes', [VehicleMakeController::class, 'apiList']);

    // ───────────────────────────────────────────────────────────────
    // Internal Notifications
    // ───────────────────────────────────────────────────────────────
    Route::get('/notifications', [NotificationController::class, 'index'])->name('app.notifications.index');
    Route::get('/api/notifications', [NotificationController::class, 'apiIndex'])->name('app.notifications.api');
    Route::get('/api/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('app.notifications.unread-count');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('app.notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('app.notifications.read-all');
    Route::post('/announcements/{announcement}/dismiss', [NotificationController::class, 'dismissAnnouncement'])->name('app.announcements.dismiss');

    // ───────────────────────────────────────────────────────────────
    // Inventory Module
    // ───────────────────────────────────────────────────────────────
    Route::prefix('inventory')->name('app.inventory.')->group(function () {
        // Hub (main dashboard)
        Route::get('/', [InventorySettingsController::class, 'hub'])->name('hub');

        // Settings (units, categories)
        Route::get('/settings', [InventorySettingsController::class, 'index'])->name('settings.index');
        Route::post('/settings/units', [InventorySettingsController::class, 'storeUnit'])->name('settings.units.store');
        Route::put('/settings/units/{unit}', [InventorySettingsController::class, 'updateUnit'])->name('settings.units.update');
        Route::delete('/settings/units/{unit}', [InventorySettingsController::class, 'destroyUnit'])->name('settings.units.destroy');
        Route::post('/settings/categories', [InventorySettingsController::class, 'storeCategory'])->name('settings.categories.store');
        Route::put('/settings/categories/{category}', [InventorySettingsController::class, 'updateCategory'])->name('settings.categories.update');
        Route::delete('/settings/categories/{category}', [InventorySettingsController::class, 'destroyCategory'])->name('settings.categories.destroy');

        // Parts
        Route::get('/parts', [PartsController::class, 'index'])->name('parts.index');
        Route::get('/parts/create', [PartsController::class, 'create'])->name('parts.create');
        Route::post('/parts', [PartsController::class, 'store'])->name('parts.store');
        Route::get('/parts/{part}/edit', [PartsController::class, 'edit'])->name('parts.edit');
        Route::put('/parts/{part}', [PartsController::class, 'update'])->name('parts.update');
        Route::patch('/parts/{part}/toggle', [PartsController::class, 'toggleActive'])->name('parts.toggle');
        Route::get('/api/parts/search', [PartsController::class, 'search'])->name('parts.search');
        Route::get('/parts/{part}', [PartsController::class, 'show'])->name('parts.show');
        Route::post('/parts/{part}/stock', [PartsController::class, 'updateStock'])->name('parts.stock.update');

        // Stock Balances
        Route::get('/stock', [InventoryBalanceController::class, 'index'])->name('stock.index');
        Route::get('/api/stock/{part}', [InventoryBalanceController::class, 'getPartStock'])->name('stock.part');

        // Inventory Moves (Ledger)
        Route::get('/moves', [InventoryMoveController::class, 'index'])->name('moves.index');
        Route::post('/moves/receipt', [InventoryMoveController::class, 'storeReceipt'])->name('moves.receipt');
        Route::post('/moves/adjustment', [InventoryMoveController::class, 'storeAdjustment'])->name('moves.adjustment');
        Route::post('/moves/{inventoryMove}/reverse', [InventoryMoveController::class, 'reverse'])->name('moves.reverse');

        // Inventory Transfers
        Route::get('/transfers', [InventoryTransfersController::class, 'index'])->name('transfers.index');
        Route::get('/transfers/create', [InventoryTransfersController::class, 'create'])->name('transfers.create');
        Route::post('/transfers', [InventoryTransfersController::class, 'store'])->name('transfers.store');
        Route::get('/transfers/{transfer}', [InventoryTransfersController::class, 'show'])->name('transfers.show');
        Route::post('/transfers/{transfer}/items', [InventoryTransfersController::class, 'addItem'])->name('transfers.items.store');
        Route::delete('/transfers/{transfer}/items/{item}', [InventoryTransfersController::class, 'removeItem'])->name('transfers.items.destroy');
        Route::post('/transfers/{transfer}/send', [InventoryTransfersController::class, 'send'])->name('transfers.send');
        Route::post('/transfers/{transfer}/receive', [InventoryTransfersController::class, 'receive'])->name('transfers.receive');
        Route::post('/transfers/{transfer}/cancel', [InventoryTransfersController::class, 'cancel'])->name('transfers.cancel');
    });

    // ───────────────────────────────────────────────────────────────
    // Purchasing Module
    // ───────────────────────────────────────────────────────────────
    Route::prefix('purchasing')->name('app.purchasing.')->group(function () {
        // Hub
        Route::get('/', [PurchasingHubController::class, 'index'])->name('hub');

        // Suppliers
        Route::get('/suppliers', [SuppliersController::class, 'index'])->name('suppliers.index');
        Route::get('/suppliers/export', [SuppliersController::class, 'export'])->name('suppliers.export');

        Route::post('/suppliers', [SuppliersController::class, 'store'])->name('suppliers.store');
        Route::get('/suppliers/{supplier}', [SuppliersController::class, 'show'])->name('suppliers.show');

        Route::put('/suppliers/{supplier}', [SuppliersController::class, 'update'])->name('suppliers.update');
        Route::delete('/suppliers/{supplier}', [SuppliersController::class, 'destroy'])->name('suppliers.destroy');
        Route::patch('/suppliers/{supplier}/toggle', [SuppliersController::class, 'toggleActive'])->name('suppliers.toggle');
        Route::get('/api/suppliers/search', [SuppliersController::class, 'search'])->name('suppliers.search');

        // Purchase Orders
        Route::get('/orders', [PurchaseOrdersController::class, 'index'])->name('orders.index');
        Route::get('/orders/create', [PurchaseOrdersController::class, 'create'])->name('orders.create');
        Route::post('/orders', [PurchaseOrdersController::class, 'store'])->name('orders.store');
        Route::get('/orders/{purchaseOrder}', [PurchaseOrdersController::class, 'show'])->name('orders.show');
        Route::put('/orders/{purchaseOrder}', [PurchaseOrdersController::class, 'update'])->name('orders.update');
        Route::post('/orders/{purchaseOrder}/send', [PurchaseOrdersController::class, 'send'])->name('orders.send');
        Route::post('/orders/{purchaseOrder}/cancel', [PurchaseOrdersController::class, 'cancel'])->name('orders.cancel');
        Route::post('/orders/{purchaseOrder}/items', [PurchaseOrdersController::class, 'addItem'])->name('orders.items.store');
        Route::delete('/orders/{purchaseOrder}/items/{item}', [PurchaseOrdersController::class, 'removeItem'])->name('orders.items.destroy');

        // Goods Received Notes
        Route::post('/orders/{purchaseOrder}/receive', [GoodsReceivedNotesController::class, 'store'])->name('grn.store');
        Route::get('/grn/{goodsReceivedNote}', [GoodsReceivedNotesController::class, 'show'])->name('grn.show');
        Route::post('/grn/{goodsReceivedNote}/post', [GoodsReceivedNotesController::class, 'post'])->name('grn.post');
        Route::post('/grn/{goodsReceivedNote}/create-invoice', [GoodsReceivedNotesController::class, 'createInvoice'])->name('grn.create-invoice');
        Route::post('/grn/{goodsReceivedNote}/cancel', [GoodsReceivedNotesController::class, 'cancel'])->name('grn.cancel');

        // Purchase Invoices (Moved to Invoices module)
        Route::get('/invoices', function () {
            return redirect()->route('app.invoices.purchases.index');
        });

        // Purchasing Module Additions (Inventory Sales & Purchases)
        Route::get('/sales', [PurchasingInvoicesController::class, 'salesIndex'])->name('sales.index');
        Route::post('/sales', [PurchasingInvoicesController::class, 'storeSalesInvoice'])->name('sales-invoices.store');
        Route::get('/purchases', [PurchasingInvoicesController::class, 'purchasesIndex'])->name('purchases.index');
    });

    // ───────────────────────────────────────────────────────────────
    // HR Module (Human Resources)
    // ───────────────────────────────────────────────────────────────
    Route::prefix('hr')->name('app.hr.')->group(function () {
        // Dashboard
        Route::get('/', [HRController::class, 'index'])->name('index');

        // Settings (employee types, job titles, allowances, deductions)
        Route::get('/settings', [App\Http\Controllers\App\HR\SettingsController::class, 'index'])->name('settings.index');

        // Employee Types
        Route::post('/settings/employee-types', [App\Http\Controllers\App\HR\SettingsController::class, 'storeEmployeeType'])->name('settings.employee-types.store');
        Route::put('/settings/employee-types/{employeeType}', [App\Http\Controllers\App\HR\SettingsController::class, 'updateEmployeeType'])->name('settings.employee-types.update');
        Route::delete('/settings/employee-types/{employeeType}', [App\Http\Controllers\App\HR\SettingsController::class, 'destroyEmployeeType'])->name('settings.employee-types.destroy');

        // Job Titles
        Route::post('/settings/job-titles', [App\Http\Controllers\App\HR\SettingsController::class, 'storeJobTitle'])->name('settings.job-titles.store');
        Route::put('/settings/job-titles/{jobTitle}', [App\Http\Controllers\App\HR\SettingsController::class, 'updateJobTitle'])->name('settings.job-titles.update');
        Route::delete('/settings/job-titles/{jobTitle}', [App\Http\Controllers\App\HR\SettingsController::class, 'destroyJobTitle'])->name('settings.job-titles.destroy');

        // Allowances
        Route::post('/settings/allowances', [HRPayrollSettingsController::class, 'storeAllowance'])->name('settings.allowances.store');
        Route::put('/settings/allowances/{allowance}', [HRPayrollSettingsController::class, 'updateAllowance'])->name('settings.allowances.update');
        Route::delete('/settings/allowances/{allowance}', [HRPayrollSettingsController::class, 'destroyAllowance'])->name('settings.allowances.destroy');

        // Deductions
        Route::post('/settings/deductions', [HRPayrollSettingsController::class, 'storeDeduction'])->name('settings.deductions.store');
        Route::put('/settings/deductions/{deduction}', [HRPayrollSettingsController::class, 'updateDeduction'])->name('settings.deductions.update');
        Route::delete('/settings/deductions/{deduction}', [HRPayrollSettingsController::class, 'destroyDeduction'])->name('settings.deductions.destroy');

        // Regulations
        Route::post('/settings/regulations', [HRRegulationsController::class, 'storeRegulation'])->name('settings.regulations.store');
        Route::put('/settings/regulations/{regulation}', [HRRegulationsController::class, 'updateRegulation'])->name('settings.regulations.update');
        Route::delete('/settings/regulations/{regulation}', [HRRegulationsController::class, 'destroyRegulation'])->name('settings.regulations.destroy');

        // Employee Permissions
        Route::get('/employees/{employee}/permissions', [EmployeePermissionsController::class, 'index'])->name('employees.permissions.index');
        Route::put('/employees/{employee}/permissions', [EmployeePermissionsController::class, 'update'])->name('employees.permissions.update');

        // Employees
        Route::get('/employees/print', [EmployeeController::class, 'print'])->name('employees.print');
        Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
        Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
        Route::get('/employees/{employee}', [EmployeeController::class, 'show'])->name('employees.show');
        Route::put('/employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
        Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy'])->name('employees.destroy');
        Route::put('/employees/{employee}/allowances', [EmployeeFinancialsController::class, 'updateAllowances'])->name('employees.allowances.update');
        Route::put('/employees/{employee}/deductions', [EmployeeFinancialsController::class, 'updateDeductions'])->name('employees.deductions.update');
        Route::post('/employees/{employee}/upload-photo', [EmployeeController::class, 'uploadPhoto'])->name('employees.upload-photo');
        Route::put('/employees/{employee}/default-shift', [EmployeeShiftController::class, 'updateDefaultShift'])->name('employees.default-shift.update');
        Route::put('/employees/{employee}/weekly-schedule', [EmployeeShiftController::class, 'updateWeeklySchedule'])->name('employees.weekly-schedule.update');

        // Employee Financial
        Route::put('/employees/{employee}/bank-info', [EmployeeFinancialsController::class, 'updateBankInfo'])->name('employees.bank-info.update');
        Route::put('/employees/{employee}/financial-info', [EmployeeFinancialsController::class, 'updateFinancialInfo'])->name('employees.financial-info.update');
        Route::put('/employees/{employee}/roles', [EmployeeController::class, 'updateRoles'])->name('employees.roles.update');

        // Documents
        Route::post('/employees/{employee}/documents', [EmployeeDocumentsController::class, 'store'])->name('employees.documents.store');
        Route::delete('/documents/{document}', [EmployeeDocumentsController::class, 'destroy'])->name('documents.destroy');

        // Contracts
        Route::post('/employees/{employee}/contracts', [EmployeeContractsController::class, 'store'])->name('employees.contracts.store');
        Route::put('/contracts/{contract}', [EmployeeContractsController::class, 'update'])->name('contracts.update');
        Route::delete('/contracts/{contract}', [EmployeeContractsController::class, 'destroy'])->name('contracts.destroy');

        // Payroll Runs
        Route::get('/payroll', [PayrollController::class, 'index'])->name('payroll.index');
        Route::post('/payroll/generate', [PayrollController::class, 'generate'])->name('payroll.generate');

        // Other Payments (MUST be before {payrollRun} wildcard routes!)
        Route::get('/payroll/other-payments', [OtherPaymentsController::class, 'index'])->name('payroll.other-payments.index');
        Route::post('/payroll/other-payments', [OtherPaymentsController::class, 'store'])->name('payroll.other-payments.store');
        Route::put('/payroll/other-payments/{otherPayment}', [OtherPaymentsController::class, 'update'])->name('payroll.other-payments.update');
        Route::delete('/payroll/other-payments/{otherPayment}', [OtherPaymentsController::class, 'destroy'])->name('payroll.other-payments.destroy');
        Route::put('/payroll/other-payments/{otherPayment}/approve', [OtherPaymentsController::class, 'approve'])->name('payroll.other-payments.approve');
        Route::put('/payroll/other-payments/{otherPayment}/pay', [OtherPaymentsController::class, 'markAsPaid'])->name('payroll.other-payments.pay');

        // Payroll Run Details (wildcard routes)
        Route::get('/payroll/{payrollRun}', [PayrollController::class, 'show'])->name('payroll.show');
        Route::put('/payroll/{payrollRun}/approve', [PayrollController::class, 'approve'])->name('payroll.approve');
        Route::put('/payroll/{payrollRun}/regenerate', [PayrollController::class, 'regenerate'])->name('payroll.regenerate');
        Route::put('/payroll/{payrollRun}/mark-paid', [PayrollController::class, 'markPaid'])->name('payroll.mark-paid');
        Route::delete('/payroll/{payrollRun}/items/{payrollItem}', [PayrollController::class, 'destroyItem'])->name('payroll.items.destroy');
        Route::get('/payroll/{payrollRun}/print', [PayrollController::class, 'print'])->name('payroll.print');

        // Employee Payroll
        Route::get('/employees/{employee}/payroll', [PayrollController::class, 'employeePayroll'])->name('employees.payroll');
        Route::get('/payroll-items/{payrollItem}/print', [PayrollController::class, 'printPaySlip'])->name('payroll.payslip');

        // Attendance
        Route::get('/attendance/print', [AttendanceController::class, 'print'])->name('attendance.print');
        Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');
        Route::put('/attendance/{attendance}', [AttendanceController::class, 'update'])->name('attendance.update');
        Route::delete('/attendance/{attendance}', [AttendanceController::class, 'destroy'])->name('attendance.destroy');

        // Leaves
        Route::post('/leaves', [LeaveController::class, 'store'])->name('leaves.store');
        Route::put('/leaves/{leave}', [LeaveController::class, 'update'])->name('leaves.update');
        Route::delete('/leaves/{leave}', [LeaveController::class, 'destroy'])->name('leaves.destroy');
        Route::put('/leaves/{leave}/status', [LeaveController::class, 'updateStatus'])->name('leaves.update-status');

        // Biometric Devices
        Route::post('/settings/biometric-devices', [BiometricDeviceController::class, 'store'])->name('settings.biometric-devices.store');
        Route::put('/settings/biometric-devices/{biometricDevice}', [BiometricDeviceController::class, 'update'])->name('settings.biometric-devices.update');
        Route::delete('/settings/biometric-devices/{biometricDevice}', [BiometricDeviceController::class, 'destroy'])->name('settings.biometric-devices.destroy');
        Route::post('/settings/biometric-devices/{biometricDevice}/regenerate-token', [BiometricDeviceController::class, 'regenerateToken'])->name('settings.biometric-devices.regenerate-token');
        Route::get('/settings/biometric-devices/{biometricDevice}/token', [BiometricDeviceController::class, 'showToken'])->name('settings.biometric-devices.show-token');

        // Shifts
        Route::post('/settings/shifts', [ShiftController::class, 'store'])->name('settings.shifts.store');
        Route::put('/settings/shifts/{shift}', [ShiftController::class, 'update'])->name('settings.shifts.update');
        Route::delete('/settings/shifts/{shift}', [ShiftController::class, 'destroy'])->name('settings.shifts.destroy');

        // Attendance Settings
        Route::put('/settings/attendance', [App\Http\Controllers\App\HR\SettingsController::class, 'updateAttendanceSettings'])->name('settings.attendance.update');
    });

    // ───────────────────────────────────────────────────────────────
    // Reports Module
    // ───────────────────────────────────────────────────────────────
    Route::prefix('reports')->name('app.reports.')->group(function () {
        Route::get('/', [ReportsController::class, 'hub'])->name('hub');
    });

    // ───────────────────────────────────────────────────────────────
    // Invoices & Payments
    // ───────────────────────────────────────────────────────────────
    Route::prefix('invoices')->name('app.invoices.')->group(function () {
        // Hub (landing page)
        Route::get('/', [InvoicesController::class, 'hub'])->name('hub');

        // Sales Invoices
        Route::get('/sales', [InvoicesController::class, 'salesIndex'])->name('sales.index');

        // Purchase Invoices
        Route::get('/purchases', [InvoicesController::class, 'purchasesIndex'])->name('purchases.index');
        Route::post('/purchases', [PurchaseInvoicesController::class, 'store'])->name('purchases.store');
        Route::get('/purchases/{purchaseInvoice}', [PurchaseInvoicesController::class, 'show'])->name('purchases.show');
        Route::get('/purchases/{purchaseInvoice}/print', [PurchaseInvoicesController::class, 'print'])->name('purchases.print');
        Route::post('/purchases/{purchaseInvoice}/attachment', [PurchaseInvoicesController::class, 'uploadAttachment'])->name('purchases.attachment.store');
        Route::delete('/purchases/{purchaseInvoice}/attachment', [PurchaseInvoicesController::class, 'destroyAttachment'])->name('purchases.attachment.destroy');
        Route::post('/purchases/{purchaseInvoice}/payments', [PurchaseInvoicesController::class, 'recordPayment'])->name('purchases.payments.store');
        Route::post('/purchases/{purchaseInvoice}/returns', [PurchaseReturnsController::class, 'recordReturn'])->name('purchases.returns.store');
        Route::get('/purchases/returns/{purchaseReturnInvoice}', [PurchaseReturnsController::class, 'showReturn'])->name('purchases.returns.show');
        Route::get('/purchases/returns/{purchaseReturnInvoice}/print', [PurchaseReturnsController::class, 'printReturn'])->name('purchases.returns.print');
        Route::post('/purchases/returns/{purchaseReturnInvoice}/refunds', [PurchaseReturnsController::class, 'recordReturnRefund'])->name('purchases.returns.refunds.store');
        Route::post('/purchases/returns/{purchaseReturnInvoice}/attachment', [PurchaseReturnsController::class, 'uploadReturnAttachment'])->name('purchases.returns.attachment.store');
        Route::delete('/purchases/returns/{purchaseReturnInvoice}/attachment', [PurchaseReturnsController::class, 'destroyReturnAttachment'])->name('purchases.returns.attachment.destroy');

        // Individual invoice show / print (existing)
        Route::get('/{invoice}', [InvoicesController::class, 'show'])->name('show');
        Route::get('/{invoice}/print', [InvoicesController::class, 'print'])->name('print');

        // Payments on Invoice
        Route::post('/{invoice}/payments', [PaymentsController::class, 'store'])->name('payments.store');
        Route::post('/{invoice}/pay-full', [PaymentsController::class, 'payFull'])->name('payments.pay-full');
    });

    // Delete payment (outside invoices prefix)
    Route::delete('/payments/{payment}', [PaymentsController::class, 'destroy'])->name('app.payments.destroy');

    // Generate invoice from work order
    Route::post('/work-orders/{workOrder}/invoice', [InvoicesController::class, 'createFromWorkOrder'])->name('app.work-orders.invoice');
    Route::get('/work-orders/{workOrder}/proforma', [InvoicesController::class, 'printProforma'])->name('app.work-orders.proforma');
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

// System Admin Panel routes
Route::prefix('system')->middleware(['auth:web,admin', 'system.admin'])->group(function () {
    // Dashboard
    Route::get('/', [SystemDashboardController::class, 'index'])->name('system.dashboard');

    // Developer Center
    Route::get('/developer', [DeveloperController::class, 'index'])->name('system.developer.index');
    Route::post('/developer/audit', [DeveloperController::class, 'runAudit'])->name('system.developer.audit');
    Route::get('/developer/graph', [DeveloperController::class, 'getGraph'])->name('system.developer.graph');
    Route::post('/developer/ai-advice', [DeveloperController::class, 'aiAdvice'])->name('system.developer.ai-advice');

    // Tenants Management — read access for any system admin
    Route::get('/tenants', [TenantsController::class, 'index'])->name('system.tenants.index');
    Route::get('/tenants/{tenant}', [TenantsController::class, 'show'])->name('system.tenants.show');

    // Destructive / privileged tenant actions — super_admin only.
    // Wrapping in a nested group keeps the outer /system prefix intact.
    Route::middleware('super_admin')->group(function () {
        Route::post('/tenants/{tenant}/suspend', [TenantsController::class, 'suspend'])->name('system.tenants.suspend');
        Route::post('/tenants/{tenant}/activate', [TenantsController::class, 'activate'])->name('system.tenants.activate');
        Route::post('/tenants/{tenant}/extend-trial', [TenantsController::class, 'extendTrial'])->name('system.tenants.extend-trial');
        Route::delete('/tenants/{tenant}', [TenantsController::class, 'destroy'])->name('system.tenants.destroy');
        Route::put('/tenants/{tenant}/security', [TenantSecurityController::class, 'update2FASettings'])->name('system.tenants.security.update');
        // Impersonation is super_admin only — powerful debugging affordance.
        Route::post('/tenants/{tenant}/impersonate', [ImpersonationController::class, 'start'])->name('system.impersonate.start');
    });

    // Communication Templates
    Route::resource('communication/templates', CommunicationTemplatesController::class)
        ->only(['index', 'edit', 'update'])
        ->names('system.communication.templates');

    // Plans Management
    // NOTE: create/edit routes removed because the Form.vue page is intentionally
    // not implemented. The Index page has a full modal-based CRUD flow that
    // posts directly to /plans and /plans/{id}. Re-introducing dedicated
    // create/edit pages would require building System/Plans/Form.vue.
    Route::get('/plans', [PlansController::class, 'index'])->name('system.plans.index');
    Route::post('/plans', [PlansController::class, 'store'])->name('system.plans.store');
    Route::put('/plans/{plan}', [PlansController::class, 'update'])->name('system.plans.update');
    Route::delete('/plans/{plan}', [PlansController::class, 'destroy'])->name('system.plans.destroy');

    // Promo Codes Management
    // See note above re: Plans — create/edit routes removed; Index.vue modal
    // handles create/update via POST/PUT to /promo-codes and /promo-codes/{id}.
    Route::get('/promo-codes', [PromoCodesController::class, 'index'])->name('system.promo-codes.index');
    Route::post('/promo-codes', [PromoCodesController::class, 'store'])->name('system.promo-codes.store');
    Route::put('/promo-codes/{promoCode}', [PromoCodesController::class, 'update'])->name('system.promo-codes.update');
    Route::delete('/promo-codes/{promoCode}', [PromoCodesController::class, 'destroy'])->name('system.promo-codes.destroy');

    // Subscriptions Management
    Route::get('/subscriptions', [SubscriptionsController::class, 'index'])->name('system.subscriptions.index');
    Route::get('/subscriptions/create', [SubscriptionsController::class, 'create'])->name('system.subscriptions.create');
    Route::post('/subscriptions', [SubscriptionsController::class, 'store'])->name('system.subscriptions.store');
    Route::get('/subscriptions/{subscription}', [SubscriptionsController::class, 'show'])->name('system.subscriptions.show');
    Route::post('/subscriptions/{subscription}/cancel', [SubscriptionsController::class, 'cancel'])->name('system.subscriptions.cancel');
    Route::post('/subscriptions/{subscription}/activate', [SubscriptionsController::class, 'activate'])->name('system.subscriptions.activate');
    Route::post('/subscriptions/{subscription}/extend', [SubscriptionsController::class, 'extend'])->name('system.subscriptions.extend');

    // Payment
    Route::get('/payment/checkout/{subscription}', [PaymentController::class, 'checkout'])->name('system.payment.checkout');
    Route::get('/payment/callback/{payment}', [PaymentController::class, 'callback'])->name('system.payment.callback');
    Route::get('/payment/success/{payment}', [PaymentController::class, 'success'])->name('system.payment.success');
    Route::get('/payment/failed/{payment}', [PaymentController::class, 'failed'])->name('system.payment.failed');
    Route::post('/payment/retry/{payment}', [PaymentController::class, 'retry'])->name('system.payment.retry');

    // Payment Settings
    Route::get('/settings/payment', [PaymentSettingsController::class, 'index'])->name('system.settings.payment');
    Route::put('/settings/payment', [PaymentSettingsController::class, 'update'])->name('system.settings.payment.update');

    // General Settings
    Route::get('/settings/general', [GeneralSettingsController::class, 'index'])->name('system.settings.general');
    Route::put('/settings/general', [GeneralSettingsController::class, 'update'])->name('system.settings.general.update');

    // Website Settings
    Route::get('/settings/website', [WebsiteSettingsController::class, 'index'])->name('system.settings.website');
    Route::put('/settings/website', [WebsiteSettingsController::class, 'update'])->name('system.settings.website.update');
    Route::post('/settings/website/upload-image', [WebsiteSettingsController::class, 'uploadImage'])->name('system.settings.website.upload-image');

    // Contact Messages
    Route::get('/settings/contact-messages', [ContactMessageController::class, 'index'])->name('system.contact_messages.index');
    Route::put('/settings/contact-messages/{contactMessage}/read', [ContactMessageController::class, 'markAsRead'])->name('system.contact_messages.read');
    Route::delete('/settings/contact-messages/{contactMessage}', [ContactMessageController::class, 'destroy'])->name('system.contact_messages.destroy');

    // Subscription Invoices
    Route::get('/invoices', [SubscriptionInvoicesController::class, 'index'])->name('system.invoices.index');
    Route::get('/invoices/{invoice}', [SubscriptionInvoicesController::class, 'show'])->name('system.invoices.show');
    Route::get('/invoices/{invoice}/download', [SubscriptionInvoicesController::class, 'download'])->name('system.invoices.download');
    Route::post('/invoices/{invoice}/send', [SubscriptionInvoicesController::class, 'send'])->name('system.invoices.send');
    Route::post('/invoices/{invoice}/mark-paid', [SubscriptionInvoicesController::class, 'markPaid'])->name('system.invoices.mark-paid');
    Route::post('/invoices/{invoice}/cancel', [SubscriptionInvoicesController::class, 'cancel'])->name('system.invoices.cancel');
    Route::post('/invoices/{invoice}/regenerate-pdf', [SubscriptionInvoicesController::class, 'regeneratePdf'])->name('system.invoices.regenerate-pdf');

    // Installments
    Route::get('/installments', [InstallmentsController::class, 'index'])->name('system.installments.index');
    Route::get('/installments/invoice/{invoice}', [InstallmentsController::class, 'show'])->name('system.installments.show');
    Route::post('/installments/{installment}/mark-paid', [InstallmentsController::class, 'markPaid'])->name('system.installments.mark-paid');
    Route::post('/installments/update-overdue', [InstallmentsController::class, 'updateOverdue'])->name('system.installments.update-overdue');

    // SMS Credits
    Route::get('/sms/packages', [SmsCreditsController::class, 'packages'])->name('system.sms.packages');
    Route::post('/sms/packages', [SmsCreditsController::class, 'storePackage'])->name('system.sms.packages.store');
    Route::put('/sms/packages/{package}', [SmsCreditsController::class, 'updatePackage'])->name('system.sms.packages.update');
    Route::delete('/sms/packages/{package}', [SmsCreditsController::class, 'destroyPackage'])->name('system.sms.packages.destroy');
    Route::get('/sms/balances', [SmsCreditsController::class, 'balances'])->name('system.sms.balances');
    Route::post('/sms/add-credits', [SmsCreditsController::class, 'addCredits'])->name('system.sms.add-credits');
    Route::get('/sms/purchases', [SmsCreditsController::class, 'purchases'])->name('system.sms.purchases');
    Route::get('/sms/usage', [SmsCreditsController::class, 'usage'])->name('system.sms.usage');

    // WhatsApp Credits
    Route::get('/whatsapp/packages', [WhatsappCreditsController::class, 'packages'])->name('system.whatsapp.packages');
    Route::post('/whatsapp/packages', [WhatsappCreditsController::class, 'storePackage'])->name('system.whatsapp.packages.store');
    Route::put('/whatsapp/packages/{package}', [WhatsappCreditsController::class, 'updatePackage'])->name('system.whatsapp.packages.update');
    Route::delete('/whatsapp/packages/{package}', [WhatsappCreditsController::class, 'destroyPackage'])->name('system.whatsapp.packages.destroy');
    Route::get('/whatsapp/balances', [WhatsappCreditsController::class, 'balances'])->name('system.whatsapp.balances');
    Route::post('/whatsapp/add-credits', [WhatsappCreditsController::class, 'addCredits'])->name('system.whatsapp.add-credits');
    Route::get('/whatsapp/usage', [WhatsappCreditsController::class, 'usage'])->name('system.whatsapp.usage');
    Route::get('/whatsapp/purchases', [WhatsappCreditsController::class, 'purchases'])->name('system.whatsapp.purchases');

    // Integrations
    Route::get('/integrations', [IntegrationsController::class, 'index'])->name('system.integrations.index');
    Route::post('/integrations', [IntegrationsController::class, 'store'])->name('system.integrations.store');
    Route::get('/integrations/{integration}', [IntegrationsController::class, 'show'])->name('system.integrations.show');
    Route::put('/integrations/{integration}', [IntegrationsController::class, 'update'])->name('system.integrations.update');
    Route::post('/integrations/{integration}/test', [IntegrationsController::class, 'test'])->name('system.integrations.test');
    Route::get('/integrations/{integration}/balance', [IntegrationsController::class, 'getBalance'])->name('system.integrations.balance');
    Route::delete('/integrations/{integration}', [IntegrationsController::class, 'destroy'])->name('system.integrations.destroy');

    // Profile
    Route::get('/profile', [App\Http\Controllers\System\ProfileController::class, 'index'])->name('system.profile.index');
    Route::put('/profile', [App\Http\Controllers\System\ProfileController::class, 'update'])->name('system.profile.update');
    Route::put('/profile/password', [App\Http\Controllers\System\ProfileController::class, 'updatePassword'])->name('system.profile.password');

    // Admin Users
    Route::get('/admin-users', [AdminUsersController::class, 'index'])->name('system.admin-users.index');
    Route::get('/admin-users/create', [AdminUsersController::class, 'create'])->name('system.admin-users.create');
    Route::post('/admin-users', [AdminUsersController::class, 'store'])->name('system.admin-users.store');
    Route::get('/admin-users/{adminUser}/edit', [AdminUsersController::class, 'edit'])->name('system.admin-users.edit');
    Route::put('/admin-users/{adminUser}', [AdminUsersController::class, 'update'])->name('system.admin-users.update');
    Route::delete('/admin-users/{adminUser}', [AdminUsersController::class, 'destroy'])->name('system.admin-users.destroy');
    Route::get('/activity-log', [AdminUsersController::class, 'activityLog'])->name('system.activity-log');

    // Announcements
    Route::get('/announcements', [AnnouncementsController::class, 'index'])->name('system.announcements.index');
    Route::get('/announcements/create', [AnnouncementsController::class, 'create'])->name('system.announcements.create');
    Route::post('/announcements', [AnnouncementsController::class, 'store'])->name('system.announcements.store');
    Route::get('/announcements/{announcement}', [AnnouncementsController::class, 'show'])->name('system.announcements.show');
    Route::post('/announcements/{announcement}/publish', [AnnouncementsController::class, 'publish'])->name('system.announcements.publish');
    Route::post('/announcements/{announcement}/unpublish', [AnnouncementsController::class, 'unpublish'])->name('system.announcements.unpublish');
    Route::delete('/announcements/{announcement}', [AnnouncementsController::class, 'destroy'])->name('system.announcements.destroy');

    // Two-Factor Authentication
    Route::get('/security/2fa', [App\Http\Controllers\System\TwoFactorController::class, 'setup'])->name('system.2fa.setup');
    Route::post('/security/2fa/send-code', [App\Http\Controllers\System\TwoFactorController::class, 'sendCode'])->name('system.2fa.send-code');
    Route::post('/security/2fa/enable', [App\Http\Controllers\System\TwoFactorController::class, 'enable'])->name('system.2fa.enable');
    Route::post('/security/2fa/disable', [App\Http\Controllers\System\TwoFactorController::class, 'disable'])->name('system.2fa.disable');
    Route::post('/security/2fa/regenerate', [App\Http\Controllers\System\TwoFactorController::class, 'regenerateRecoveryCodes'])->name('system.2fa.regenerate');

    // NOTE: /system/tenants/{tenant}/impersonate is registered above inside
    // the super_admin-only group. The duplicate declaration here was
    // removed to avoid route-name collisions and to make the privilege
    // boundary explicit in one place.
});

// 2FA Challenge (during login, before auth)
Route::middleware('web')->group(function () {
    Route::get('/2fa/challenge', [App\Http\Controllers\System\TwoFactorController::class, 'challenge'])->name('2fa.challenge');
    Route::post('/2fa/verify', [App\Http\Controllers\System\TwoFactorController::class, 'verify'])->name('2fa.verify');
});

// Stop Impersonation (accessible from anywhere when impersonating)
Route::post('/impersonate/stop', [ImpersonationController::class, 'stop'])
    ->name('impersonate.stop')
    ->middleware(['web', 'auth']);

require __DIR__.'/auth.php';
