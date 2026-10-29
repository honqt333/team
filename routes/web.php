<?php

declare(strict_types=1);

use App\Exports\AgingReportExport;
use App\Exports\BankTransferReceiptsExport;
use App\Exports\TenantsReportExport;
use App\Http\Controllers\App\AppointmentController;
use App\Http\Controllers\App\AppointmentMessageDeliveryController;
use App\Http\Controllers\App\BillingController;
use App\Http\Controllers\App\BranchesController;
use App\Http\Controllers\App\CenterSettingsController;
use App\Http\Controllers\App\CompanyProfileController;
use App\Http\Controllers\App\CompanySubscriptionController;
use App\Http\Controllers\App\CompanyTransactionController;
use App\Http\Controllers\App\CustomerController;
use App\Http\Controllers\App\CustomerImportExportController;
use App\Http\Controllers\App\CustomerMergeController;
use App\Http\Controllers\App\DashboardController;
use App\Http\Controllers\App\DepartmentController;
use App\Http\Controllers\App\EmployeePortalController;
use App\Http\Controllers\App\FeedbackController;
use App\Http\Controllers\App\GoodsReceivedNotesController;
use App\Http\Controllers\App\HelpController;
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
use App\Http\Controllers\App\ServiceBayController;
use App\Http\Controllers\App\ServiceController;
use App\Http\Controllers\App\SettingsController;
use App\Http\Controllers\App\SuppliersController;
use App\Http\Controllers\App\SystemSettingsController;
use App\Http\Controllers\App\TenantWebsiteSettingsController;
use App\Http\Controllers\App\TwoFactorAuthenticatedSessionController;
use App\Http\Controllers\App\TwoFactorController;
use App\Http\Controllers\App\UserController;
use App\Http\Controllers\App\VehicleColorController;
use App\Http\Controllers\App\VehicleConditionCategoryController;
use App\Http\Controllers\App\VehicleConditionItemController;
use App\Http\Controllers\App\VehicleController;
use App\Http\Controllers\App\VehicleMakeController;
use App\Http\Controllers\App\VehicleMergeController;
use App\Http\Controllers\App\VehicleMileageController;
use App\Http\Controllers\App\VehicleModelController;
use App\Http\Controllers\App\WorkOrderInspectionController;
use App\Http\Controllers\App\WorkOrders\WorkOrderController;
use App\Http\Controllers\App\WorkOrders\WorkOrderItemController;
use App\Http\Controllers\App\WorkOrders\WorkOrderItemStatusController;
use App\Http\Controllers\App\WorkOrders\WorkOrderLaborEntryController;
use App\Http\Controllers\App\WorkOrders\WorkOrderMediaController;
use App\Http\Controllers\App\WorkOrders\WorkOrderNotesController;
use App\Http\Controllers\App\WorkOrders\WorkOrderPartsController;
use App\Http\Controllers\App\WorkOrders\WorkOrderPaymentController;
use App\Http\Controllers\App\WorkOrders\WorkOrderPrintController;
use App\Http\Controllers\App\WorkOrders\WorkOrderStatusController;
use App\Http\Controllers\App\WorkOrders\WorkOrderTechnicianController;
use App\Http\Controllers\App\WorkOrders\WorkOrderWarrantiesController;
use App\Http\Controllers\App\WorkOrderSignatureController;
use App\Http\Controllers\App\WorkshopBoardController;
use App\Http\Controllers\App\ZatcaOnboardingController;
use App\Http\Controllers\Auth\PhoneVerificationController;
use App\Http\Controllers\Auth\SetPasswordController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Public\CustomerPortalAppointmentActionsController;
use App\Http\Controllers\Public\CustomerPortalAppointmentController;
use App\Http\Controllers\Public\CustomerPortalAuthController;
use App\Http\Controllers\Public\CustomerPortalController;
use App\Http\Controllers\Public\PublicLandingController;
use App\Http\Controllers\Public\PublicQuoteController;
use App\Http\Controllers\Public\TenantSiteController;
use App\Http\Controllers\System\AdminNotificationController;
use App\Http\Controllers\System\AdminUsersController;
use App\Http\Controllers\System\AnalyticsController;
use App\Http\Controllers\System\AnnouncementsController;
use App\Http\Controllers\System\BackupController;
use App\Http\Controllers\System\BankTransferReceiptsController;
use App\Http\Controllers\System\CommunicationTemplatesController;
use App\Http\Controllers\System\CompanySettingsController;
use App\Http\Controllers\System\ContactMessageController;
use App\Http\Controllers\System\DeveloperController;
use App\Http\Controllers\System\DunningController;
use App\Http\Controllers\System\FeedbackManagementController;
use App\Http\Controllers\System\GeneralSettingsController;
use App\Http\Controllers\System\HelpSettingsController;
use App\Http\Controllers\System\ImpersonationController;
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
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

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

// ─────────────────────────────────────────────────────────────────────────────
// Tenant Public Sites (Phase 6)
// ─────────────────────────────────────────────────────────────────────────────
//
// Each tenant has a public website mounted under its slug:
//     /{tenant-slug}        → public homepage
//     /{tenant-slug}/portal → customer portal
//
// The {tenant} parameter is the tenant SLUG (the unique key on
// tenants table), not a tenant_id. ResolveTenantFromUrl turns
// the slug into a Tenant + TenantWebsite pair, and 404s on
// missing or unpublished sites. We do not use the standard
// {tenant} route binding because that would resolve to a model
// by id, which is wrong here.
//
// Reserved-slot exclusion: the where() regex pattern keeps this
// catch-all from shadowing the platform's other top-level paths
// (login, register, dashboard, app, system, etc.). The
// negative-lookahead alternation matches anything EXCEPT the
// reserved words. Add a new reserved word here whenever a new
// top-level public route is introduced.
Route::get('/{tenant}', [TenantSiteController::class, 'show'])
    ->where('tenant', '^(?!(?:app|system|dashboard|login|logout|register|password|forgot-password|reset-password|verify-email|confirm-password|two-factor|2fa|locale|phone|lang|landing-preview|view|impersonate|impersonate-stop|up|storage|livewire|invitations)$)[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$')
    ->middleware('public.tenant')
    ->name('public.site.show');

// ─────────────────────────────────────────────────────────────────────────────
// Customer Portal (Phase 7)
// ─────────────────────────────────────────────────────────────────────────────
//
// Mounted at /{tenant}/portal/*. The {tenant} param has the same
// reserved-word exclusion as the public site route above. Each
// route lives in the `public.tenant` middleware group (to resolve
// the tenant), and protected routes additionally run
// `customer.portal.session` (to validate the OTP session).
//
// The portal session is a separate guard, separate cookie,
// separate lifetime from the staff session. The middleware
// chain ensures no staff cookies ever authenticate the portal
// and vice versa.
Route::prefix('/{tenant}/portal')
    ->where(['tenant' => '^(?!(?:app|system|dashboard|login|logout|register|password|forgot-password|reset-password|verify-email|confirm-password|two-factor|2fa|locale|phone|lang|landing-preview|view|impersonate|impersonate-stop|up|storage|livewire|invitations)$)[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$'])
    ->middleware('public.tenant')
    ->name('public.portal.')
    ->group(function () {
        // Auth endpoints — public.tenant only; no portal
        // session required (these are how you GET the
        // session).
        Route::get('/login', [CustomerPortalAuthController::class, 'showLogin'])
            ->name('login');
        Route::post('/login', [CustomerPortalAuthController::class, 'requestOtp'])
            ->middleware('throttle:customer-portal-otp')
            ->name('login.otp');
        Route::get('/verify', [CustomerPortalAuthController::class, 'showVerify'])
            ->name('verify');
        Route::post('/verify', [CustomerPortalAuthController::class, 'verifyOtp'])
            ->middleware('throttle:customer-portal-verify')
            ->name('verify.otp');
        Route::post('/logout', [CustomerPortalAuthController::class, 'logout'])
            ->name('logout');
        // Language toggle: persists the user's choice in a
        // per-tenant cookie. The query-string `?lang=`
        // shape on the redirect lets the very next render
        // pick up the new locale without a round-trip.
        Route::post('/locale', [CustomerPortalAuthController::class, 'setLocale'])
            ->name('locale');

        // Protected endpoints — must have a valid session.
        Route::middleware('customer.portal.session')->group(function () {
            Route::get('/dashboard', [CustomerPortalController::class, 'dashboard'])
                ->name('dashboard');

            // ─── Self-service booking (Phase 8) ───
            // The booking endpoints run behind the
            // portal session middleware, so the
            // controller can trust portal_customer is
            // set. Tenant + customer isolation live
            // in the controller; the route itself
            // only enforces auth + throttling.
            //
            // Route ordering matters. Laravel matches
            // by registration order, NOT by specificity,
            // so the catch-all `/appointments/{id}`
            // would shadow `/appointments/new` and
            // `/appointments/availability` if it came
            // first. The literal routes come first.
            Route::get('/appointments/new', [CustomerPortalAppointmentController::class, 'showNew'])
                ->name('appointments.new');
            Route::get('/appointments/availability', [CustomerPortalAppointmentController::class, 'availability'])
                ->middleware('throttle:customer-portal-availability')
                ->name('appointments.availability');
            Route::post('/appointments', [CustomerPortalAppointmentController::class, 'store'])
                ->middleware('throttle:customer-portal-booking')
                ->name('appointments.store');
            // The catch-all confirmation route uses a
            // different path shape from the literal
            // routes above so Laravel can never
            // confuse a number like "1" with one of
            // the literal segments.
            Route::get('/appointment/{id}', [CustomerPortalAppointmentController::class, 'showConfirmation'])
                ->where(['id' => '\d+'])
                ->name('appointments.show');

            // ─── Self-service cancel + reschedule (Phase 9) ───
            // The cancel + reschedule endpoints are
            // kept on a separate controller from the
            // booking flow so each phase's audit log
            // + tests stay focused. The actions live
            // under the same `customer.portal.session`
            // middleware so the controllers can trust
            // `portal_customer` and `public_tenant`.
            Route::get('/appointment/{id}/edit', [CustomerPortalAppointmentActionsController::class, 'showReschedule'])
                ->where(['id' => '\d+'])
                ->name('appointments.edit');
            Route::post('/appointment/{id}/cancel', [CustomerPortalAppointmentActionsController::class, 'cancel'])
                ->middleware('throttle:customer-portal-cancel')
                ->where(['id' => '\d+'])
                ->name('appointments.cancel');
            Route::get('/appointment/{id}/reschedule-availability', [CustomerPortalAppointmentActionsController::class, 'rescheduleAvailability'])
                ->middleware('throttle:customer-portal-reschedule-availability')
                ->where(['id' => '\d+'])
                ->name('appointments.rescheduleAvailability');
            Route::post('/appointment/{id}/reschedule', [CustomerPortalAppointmentActionsController::class, 'reschedule'])
                ->middleware('throttle:customer-portal-reschedule')
                ->where(['id' => '\d+'])
                ->name('appointments.reschedule');
        });
    });

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

// The dashboard is the app's landing page and it renders the tenant's
// operating and financial figures. When the tenant requires 2FA, a
// user who has not confirmed it must not reach it — the guard used to
// be missing here, so the requirement was bypassed on exactly the
// page that shows the most.
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'tenant.active', 'center.context', EnsureTwoFactorEnabled::class])
    ->name('dashboard');

// 2FA Challenge (during login)
Route::get('/app/2fa/challenge', [TwoFactorAuthenticatedSessionController::class, 'challenge'])->name('app.2fa.challenge');
Route::post('/app/2fa/verify', [TwoFactorAuthenticatedSessionController::class, 'verify'])->middleware('throttle:2fa-verify')->name('app.2fa.verify');
Route::post('/app/2fa/challenge/resend', [TwoFactorAuthenticatedSessionController::class, 'resend'])->middleware('throttle:phone-otp')->name('app.2fa.challenge.resend');

// The profile and the employee portal sat in a group without the 2FA
// guard, so a tenant that requires 2FA could not enforce it on its
// own staff. The guard exempts the `app.security.2fa.*` routes (see
// EnsureTwoFactorEnabled), and the setup route lives in this group,
// so an unconfirmed user is sent to the setup screen and not into a
// redirect loop.
Route::middleware(['auth', 'verified', EnsureTenantActive::class, EnsureTwoFactorEnabled::class])->prefix('app')->group(function () {
    // Profile
    Route::get('/profile', [App\Http\Controllers\App\ProfileController::class, 'index'])->name('app.profile');
    Route::patch('/profile', [App\Http\Controllers\App\ProfileController::class, 'update'])->name('app.profile.update');
    Route::post('/profile/photo', [App\Http\Controllers\App\ProfileController::class, 'updatePhoto'])->middleware('throttle:uploads')->name('app.profile.photo.update');
    Route::post('/profile/switch-center', [ProfileController::class, 'switchCenter'])->name('profile.switch-center');

    // Two-Factor Authentication (Tenant App)
    //
    // The setup page needs a GET route of its own. The
    // EnsureTwoFactorEnabled middleware redirects a user who has not
    // confirmed 2FA yet to `app.security.2fa.setup`; without this
    // route the redirect target did not exist and the middleware
    // raised a 500 instead of showing the setup screen. The Vue page
    // (App/Security/TwoFactorSetup) and the controller action were
    // already there — only the route was missing.
    Route::get('/security/2fa', [TwoFactorController::class, 'setup'])->name('app.security.2fa.setup');
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

    // The duplicate lookup answers with the matching customer's
    // contact details, so it is permission-gated in the controller and
    // rate-limited here.
    Route::get('/customers/check-phone', [CustomerController::class, 'checkPhone'])
        ->middleware('throttle:record-lookup')
        ->name('customers.check-phone');
    Route::get('/customers/{customer}/statement/print', [CustomerController::class, 'printStatement'])->name('customers.statement.print');
    Route::resource('customers', CustomerController::class);
    // Customer merge routes
    Route::get('/customers/{customer}/merge', [CustomerMergeController::class, 'mergeData'])->name('customers.merge-data');
    Route::post('/customers/{source}/merge/{target}', [CustomerMergeController::class, 'executeMerge'])->name('customers.execute-merge');

    // Vehicle export/print routes
    Route::get('/vehicles/export', [VehicleController::class, 'export'])->name('vehicles.export');
    Route::get('/vehicles/print', [VehicleController::class, 'print'])->name('vehicles.print');
    // The duplicate lookup answers with the matching vehicle and its
    // owning customer's contact details, so it is permission-gated in
    // the controller and rate-limited here.
    Route::get('/vehicles/check-plate', [VehicleController::class, 'checkPlate'])
        ->middleware('throttle:record-lookup')
        ->name('vehicles.check-plate');
    Route::apiResource('vehicles', VehicleController::class);
    Route::get('/vehicles/{vehicle}/mileage-logs', [VehicleMileageController::class, 'index'])->name('vehicles.mileage-logs.index');
    Route::delete('/vehicles/{vehicle}/mileage-logs/{log}', [VehicleMileageController::class, 'destroy'])->name('vehicles.mileage-logs.destroy');
    Route::get('/vehicles/{vehicle}/merge', [VehicleMergeController::class, 'mergeData'])->name('vehicles.merge-data');
    Route::post('/vehicles/{source}/merge/{target}', [VehicleMergeController::class, 'executeMerge'])->name('vehicles.execute-merge');

    // Work Orders - Hub and Index
    Route::get('/work-orders/export', [WorkOrderController::class, 'export'])->name('work-orders.export');

    // Workshop board (Phase 1: read-only Kanban for active work order items)
    Route::get('/workshop', [WorkshopBoardController::class, 'index'])->name('app.workshop.index');

    // Phase 4: Service Bays (settings + admin)
    Route::get('/service-bays', [ServiceBayController::class, 'index'])->name('service-bays.direct-index');
    Route::get('/settings/service-bays', [ServiceBayController::class, 'index'])->name('service-bays.index');
    Route::post('/service-bays', [ServiceBayController::class, 'store'])->name('service-bays.store');
    Route::post('/settings/service-bays', [ServiceBayController::class, 'store']);
    Route::put('/service-bays/{serviceBay}', [ServiceBayController::class, 'update'])->name('service-bays.update');
    Route::delete('/service-bays/{serviceBay}', [ServiceBayController::class, 'destroy'])->name('service-bays.destroy');

    // Phase 4: Appointments
    Route::get('/appointments', [AppointmentController::class, 'index'])->name('appointments.index');
    Route::post('/appointments', [AppointmentController::class, 'store'])->name('appointments.store');
    Route::put('/appointments/{appointment}', [AppointmentController::class, 'update'])->name('appointments.update');
    Route::post('/appointments/{appointment}/check-in', [AppointmentController::class, 'checkIn'])->name('appointments.check-in');
    Route::post('/appointments/{appointment}/no-show', [AppointmentController::class, 'markNoShow'])->name('appointments.no-show');
    Route::post('/appointments/{appointment}/cancel', [AppointmentController::class, 'cancel'])->name('appointments.cancel');
    Route::post('/appointments/{appointment}/convert', [AppointmentController::class, 'convert'])->name('appointments.convert');

    // Phase 11: Appointment message delivery log
    // (failed / skipped SMS about an appointment).
    // The retry endpoint is throttled so a
    // double-click or a runaway script cannot
    // hammer the queue.
    Route::get('/communications/appointment-messages', [AppointmentMessageDeliveryController::class, 'index'])
        ->name('communications.appointment-messages.index');
    Route::post('/communications/appointment-messages/{delivery}/retry', [AppointmentMessageDeliveryController::class, 'retry'])
        ->where(['delivery' => '\d+'])
        ->middleware('throttle:customer-portal-booking')
        ->name('communications.appointment-messages.retry');
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

    // Work Order Item Status (Phase 3: per-item hold + QC)
    Route::post('/work-orders/{work_order}/items/{item}/hold', [WorkOrderItemStatusController::class, 'hold'])->name('work-orders.items.hold');
    Route::post('/work-orders/{work_order}/items/{item}/resume', [WorkOrderItemStatusController::class, 'resume'])->name('work-orders.items.resume');
    Route::post('/work-orders/{work_order}/items/{item}/quality/approve', [WorkOrderItemStatusController::class, 'approveQuality'])->name('work-orders.items.quality.approve');
    Route::post('/work-orders/{work_order}/items/{item}/quality/reject', [WorkOrderItemStatusController::class, 'rejectQuality'])->name('work-orders.items.quality.reject');

    // Work Order Labor Entries (per-technician timer sessions)
    Route::post('/work-orders/{work_order}/items/{item}/labor-entries/start', [WorkOrderLaborEntryController::class, 'start'])->name('work-orders.items.labor.start');
    Route::post('/work-orders/{work_order}/labor-entries/{entry}/pause', [WorkOrderLaborEntryController::class, 'pause'])->name('work-orders.labor.pause');
    Route::post('/work-orders/{work_order}/labor-entries/{entry}/resume', [WorkOrderLaborEntryController::class, 'resume'])->name('work-orders.labor.resume');
    Route::post('/work-orders/{work_order}/labor-entries/{entry}/end', [WorkOrderLaborEntryController::class, 'end'])->name('work-orders.labor.end');

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
    Route::get('/quotes/export', [QuoteController::class, 'export'])->name('app.quotes.export');
    Route::get('/quotes', [QuoteController::class, 'index'])->name('app.quotes.index');
    Route::post('/quotes', [QuoteController::class, 'store'])->name('app.quotes.store');
    Route::post('/quotes/{quote}/duplicate', [QuoteController::class, 'duplicate'])->name('app.quotes.duplicate');
    Route::put('/quotes/{quote}', [QuoteController::class, 'update'])->name('app.quotes.update');
    Route::delete('/quotes/{quote}', [QuoteController::class, 'destroy'])->name('app.quotes.destroy');
    Route::post('/quotes/{quote}/approve', [QuoteApprovalController::class, 'approve'])->name('app.quotes.approve');
    Route::post('/quotes/{quote}/signature', [QuoteController::class, 'saveSignature'])->name('app.quotes.signature.store');
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
    Route::get('/addons', [SettingsController::class, 'integrations'])->name('app.addons');
    Route::get('/integrations', fn () => redirect()->route('settings.integrations'));
    // /settings/website is a placeholder card on the settings
    // hub. The real edit form lives at /settings/website/edit;
    // we redirect so the hub card opens the actual form.
    Route::get('/settings/website', fn () => redirect()->route('settings.website.edit'))->name('settings.website');

    // Tenant website settings (Phase 6)
    // The view is the existing Settings/Website.vue page; the
    // controller backs the form. The delete-image endpoint
    // lives at the same URL with a different verb to keep the
    // UI surface area small.
    Route::get('/settings/website/edit', [TenantWebsiteSettingsController::class, 'edit'])
        ->middleware('permission:settings.website.view')
        ->name('settings.website.edit');
    Route::put('/settings/website/edit', [TenantWebsiteSettingsController::class, 'update'])
        ->middleware('permission:settings.website.manage')
        ->name('settings.website.update');
    Route::delete('/settings/website/edit/image', [TenantWebsiteSettingsController::class, 'deleteImage'])
        ->middleware('permission:settings.website.manage')
        ->name('settings.website.image.delete');
    Route::post('/settings/website/edit/banner-image', [TenantWebsiteSettingsController::class, 'uploadBannerImage'])
        ->middleware('permission:settings.website.manage')
        ->name('settings.website.banner.upload');

    // Company Profile Settings
    Route::get('/settings/company', [CompanyProfileController::class, 'index'])->name('settings.company');
    Route::put('/settings/company', [CompanyProfileController::class, 'update'])->name('settings.company.update');
    Route::put('/settings/company/admin-user', [CompanyProfileController::class, 'updateAdminUser'])->name('settings.company.admin-user');
    Route::post('/settings/company/verify-password', [CompanyProfileController::class, 'verifyPassword'])->name('settings.company.verify-password');
    Route::post('/settings/company/logo', [CompanyProfileController::class, 'uploadLogo'])->name('settings.company.logo.upload');
    Route::delete('/settings/company/logo', [CompanyProfileController::class, 'deleteLogo'])->name('settings.company.logo.delete');

    // Company Transactions Settings
    Route::get('/settings/company/contacts/search', [CompanyTransactionController::class, 'searchContacts'])->name('settings.company.contacts.search');
    // Only the write routes are registered. `Route::resource` also
    // declares index/show/create/edit, and the controller implements
    // none of them — every one of those URLs answered HTTP 500 with
    // "method does not exist". The company page hosts the
    // create/edit modal, so the standalone pages are not needed.
    Route::resource('/settings/company/transactions', CompanyTransactionController::class)
        ->only(['store', 'update', 'destroy'])
        ->names('settings.company.transactions');
    Route::post('/settings/company/transactions/{transaction}/approve', [CompanyTransactionController::class, 'approve'])->name('settings.company.transactions.approve');

    // ZATCA Phase 2 Self-Onboarding
    Route::prefix('settings/zatca')->name('settings.zatca.')->group(function () {
        Route::get('/', [ZatcaOnboardingController::class, 'index'])->name('index');
        Route::post('/csr', [ZatcaOnboardingController::class, 'generateCsr'])->name('csr');
        Route::post('/compliance', [ZatcaOnboardingController::class, 'requestCompliance'])->name('compliance');
        Route::post('/check', [ZatcaOnboardingController::class, 'runChecks'])->name('check');
        Route::post('/production', [ZatcaOnboardingController::class, 'requestProduction'])->name('production');
        Route::post('/reset', [ZatcaOnboardingController::class, 'reset'])->name('reset');
    });

    // ─────────────────────────────────────────────────────────────────────
    // Billing Hub (tenant self-service) — unified subscription + payment page
    // Replaces the old /settings/company/subscription page. All POST endpoints
    // (subscribe / cancel / resume / receipt) are still mounted on
    // /settings/billing/* so the Vue page does not have to mix route names.
    // Uses payment settings configured by system admin (system/settings/payment)
    // ─────────────────────────────────────────────────────────────────────
    Route::prefix('settings/billing')->name('app.billing.')->group(function () {
        Route::get('/', [BillingController::class, 'index'])->name('index');
        Route::post('/calculate', [CompanySubscriptionController::class, 'calculatePrice'])->name('calculate');
        Route::post('/validate-promo', [CompanySubscriptionController::class, 'validatePromo'])->name('validate-promo');
        Route::post('/subscribe', [CompanySubscriptionController::class, 'subscribe'])->name('subscribe');
        Route::post('/cancel-pending', [CompanySubscriptionController::class, 'cancelPending'])->name('cancel-pending');
        // Moyasar (and most other gateways) redirect the browser to the
        // callback URL with a GET request + ?id=...&status=... query params
        // after the 3DS challenge. POST-only would block the entire success
        // path. Accept both methods — the controller is idempotent.
        Route::match(['get', 'post'], '/callback/{payment}', [CompanySubscriptionController::class, 'callback'])->name('callback');
        Route::post('/cancel/{subscription}', [CompanySubscriptionController::class, 'cancel'])->name('cancel');
        Route::post('/resume/{subscription}', [CompanySubscriptionController::class, 'resume'])->name('resume');
        Route::post('/auto-renew/{subscription}', [CompanySubscriptionController::class, 'toggleAutoRenew'])->name('auto-renew');
        // Create new subscription + invoice + receipt from a bank
        // transfer upload (atomic — no pre-existing subscription needed).
        Route::post('/bank-transfer-receipt', [CompanySubscriptionController::class, 'uploadBankTransferReceipt'])->name('receipt.bank_transfer');
        // Real-time duplicate-reference check used by the upload form.
        // GET (idempotent, cacheable) + scoped to the authenticated tenant.
        Route::get('/bank-transfer-receipt/check-reference', [CompanySubscriptionController::class, 'checkTransferReference'])->name('receipt.bank_transfer.check_reference');
        // Legacy: attach a receipt to an already-existing subscription.
        // Kept for backwards-compat with any external links.
        Route::post('/receipt/{subscription}', [CompanySubscriptionController::class, 'uploadReceipt'])->name('receipt.upload');

        // Phase 6: tenant-facing dunning preferences (opt-out +
        // language). The DunningService checks
        // tenant.dunning_opted_out before sending any reminder,
        // so toggling this off stops all future collection emails.
        Route::post('/dunning-preferences', [CompanySubscriptionController::class, 'updateDunningPreferences'])->name('dunning.preferences');
    });

    // Backwards-compat redirects — the old /settings/company/subscription/*
    // URLs now point at the new billing hub. POST endpoints are still
    // mounted on /settings/billing/* above; only the GET pages redirect.
    Route::get('/settings/company/subscription', fn () => redirect()->route('app.billing.index'))->name('app.subscription.index');
    Route::get('/settings/company/subscription/receipts', fn () => redirect()->route('app.billing.index'))->name('app.subscription.receipts');

    // Old POST endpoints (still used by external links / API clients) —
    // kept under the old prefix for backwards compat. The Vue hub
    // uses /settings/billing/* for all live calls.
    Route::prefix('settings/company/subscription')->name('app.subscription.compat.')->group(function () {
        Route::post('/calculate', [CompanySubscriptionController::class, 'calculatePrice'])->name('calculate');
        Route::post('/validate-promo', [CompanySubscriptionController::class, 'validatePromo'])->name('validate-promo');
        Route::post('/subscribe', [CompanySubscriptionController::class, 'subscribe'])->name('subscribe');
        Route::post('/cancel-pending', [CompanySubscriptionController::class, 'cancelPending'])->name('cancel-pending');
        Route::match(['get', 'post'], '/callback/{payment}', [CompanySubscriptionController::class, 'callback'])->name('callback');
        Route::post('/cancel/{subscription}', [CompanySubscriptionController::class, 'cancel'])->name('cancel');
        Route::post('/resume/{subscription}', [CompanySubscriptionController::class, 'resume'])->name('resume');
        Route::post('/auto-renew/{subscription}', [CompanySubscriptionController::class, 'toggleAutoRenew'])->name('auto-renew');
        Route::post('/bank-transfer-receipt', [CompanySubscriptionController::class, 'uploadBankTransferReceipt'])->name('receipt.bank_transfer');
        Route::post('/receipt/{subscription}', [CompanySubscriptionController::class, 'uploadReceipt'])->name('receipt.upload');
    });

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
    Route::get('/api/customers/{customer}/balance', [App\Http\Controllers\Api\WorkOrderController::class, 'customerBalance']);
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
        Route::get('/suppliers/import/template', [SuppliersController::class, 'downloadTemplate'])->name('suppliers.import.template');
        Route::post('/suppliers/import', [SuppliersController::class, 'import'])->name('suppliers.import');

        Route::post('/suppliers', [SuppliersController::class, 'store'])->name('suppliers.store');
        Route::get('/suppliers/{supplier}', [SuppliersController::class, 'show'])->name('suppliers.show');

        Route::put('/suppliers/{supplier}', [SuppliersController::class, 'update'])->name('suppliers.update');
        Route::delete('/suppliers/{supplier}', [SuppliersController::class, 'destroy'])->name('suppliers.destroy');
        Route::patch('/suppliers/{supplier}/toggle', [SuppliersController::class, 'toggleActive'])->name('suppliers.toggle');
        Route::post('/suppliers/{supplier}/payments', [SuppliersController::class, 'recordPayment'])->name('suppliers.payments.store');
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
        Route::get('/documents/{document}/download', [EmployeeDocumentsController::class, 'download'])->name('documents.download');
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
        // POST, not GET: the endpoint issues a fresh token and revokes
        // the previous one, so it must not be a safe/idempotent method
        // (a GET could be triggered by a prefetch, a crawler, or a
        // cached page and would silently rotate the credential).
        Route::post('/settings/biometric-devices/{biometricDevice}/token', [BiometricDeviceController::class, 'showToken'])->name('settings.biometric-devices.show-token');

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
        Route::get('/payments', [ReportsController::class, 'payments'])->name('payments');
        Route::get('/payments/export', [ReportsController::class, 'exportPayments'])->name('payments.export');
        Route::get('/tickets', [ReportsController::class, 'tickets'])->name('tickets');
        Route::get('/tickets/export', [ReportsController::class, 'exportTickets'])->name('tickets.export');
        Route::get('/orders', fn () => redirect()->route('app.reports.tickets'))->name('orders');
        Route::get('/services', [ReportsController::class, 'services'])->name('services');
        Route::get('/services/export', [ReportsController::class, 'exportServices'])->name('services.export');
        Route::get('/customers', [ReportsController::class, 'customers'])->name('customers');
        Route::get('/customers/export', [ReportsController::class, 'exportCustomers'])->name('customers.export');
    });

    // ───────────────────────────────────────────────────────────────
    // Help & Knowledge Base
    // ───────────────────────────────────────────────────────────────
    Route::get('/help', [HelpController::class, 'index'])->name('app.help.index');

    // ───────────────────────────────────────────────────────────────
    // Feedback & Feature Requests
    // ───────────────────────────────────────────────────────────────
    Route::prefix('feedback')->name('app.feedback.')->group(function () {
        Route::get('/', [FeedbackController::class, 'index'])->name('index');
        Route::post('/', [FeedbackController::class, 'store'])->name('store');
        Route::get('/{feedback}/video', [FeedbackController::class, 'streamVideo'])->name('video');
        Route::get('/{feedback}/reply-video', [FeedbackController::class, 'streamReplyVideo'])->name('reply-video');
        Route::post('/{feedback}/seen-reply', [FeedbackController::class, 'markReplySeen'])->name('seen-reply');
        Route::delete('/{feedback}', [FeedbackController::class, 'destroy'])->name('destroy');
    });
    Route::get('/feedbacks', fn () => redirect()->route('app.feedback.index'));

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

        // Credit Note (Return Invoice) on Invoice
        Route::get('/{invoice}/credit-note/summary', [InvoicesController::class, 'invoiceCreditNoteSummary'])->name('credit-note.summary');
        Route::post('/{invoice}/credit-note', [InvoicesController::class, 'storeInvoiceCreditNote'])->name('credit-note.store');
    });

    // Delete payment (outside invoices prefix)
    Route::delete('/payments/{payment}', [PaymentsController::class, 'destroy'])->name('app.payments.destroy');

    // Generate invoice from work order
    Route::post('/work-orders/{workOrder}/invoice', [InvoicesController::class, 'createFromWorkOrder'])->name('app.work-orders.invoice');
    Route::get('/work-orders/{workOrder}/proforma', [InvoicesController::class, 'printProforma'])->name('app.work-orders.proforma');
    Route::get('/work-orders/{workOrder}/credit-note/summary', [InvoicesController::class, 'creditNoteSummary'])->name('app.work-orders.credit-note.summary');
    Route::post('/work-orders/{workOrder}/credit-note', [InvoicesController::class, 'storeCreditNote'])->name('app.work-orders.credit-note.store');
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

    // Phase 5 — Analytics dashboard data. The Inertia `kpis` page
    // fetches all the headline numbers in one round-trip; topPlans
    // and paymentMix are separate JSON endpoints so the front end
    // can lazy-load the chart data after the KPI cards render.
    Route::get('/analytics/kpis', [AnalyticsController::class, 'kpis'])->name('system.analytics.kpis');
    Route::get('/analytics/top-plans', [AnalyticsController::class, 'topPlans'])->name('system.analytics.top-plans');
    Route::get('/analytics/payment-mix', [AnalyticsController::class, 'paymentMix'])->name('system.analytics.payment-mix');
    Route::get('/analytics/cohort-retention', [AnalyticsController::class, 'cohortRetention'])->name('system.analytics.cohort-retention');
    Route::get('/analytics/tenants/{tenant}/health-trend', [AnalyticsController::class, 'tenantHealthTrend'])->name('system.analytics.tenant-health-trend');

    // Phase 5 — Reports page (entry point for the 3 export buttons).
    // The page itself is just a UI — the actual downloads live on
    // /system/reports/* and stream the file directly.
    Route::get('/analytics/reports', function () {
        return Inertia::render('System/Analytics/Reports');
    })->name('system.analytics.reports');

    // Phase 5 — Reports & exports. Each route streams a download —
    // .xlsx for full exports, .csv for streaming-friendly aging report.
    // The Excel facade auto-handles the filename and content-type.
    Route::get('/reports/receipts', function () {
        return Excel::download(
            new BankTransferReceiptsExport,
            'receipts-'.now()->format('Y-m-d').'.xlsx'
        );
    })->name('system.reports.receipts');

    Route::get('/reports/tenants', function () {
        return Excel::download(
            new TenantsReportExport,
            'tenants-'.now()->format('Y-m-d').'.xlsx'
        );
    })->name('system.reports.tenants');

    Route::get('/reports/aging', function () {
        return Excel::download(
            new AgingReportExport,
            'aging-'.now()->format('Y-m-d').'.xlsx'
        );
    })->name('system.reports.aging');

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
        // Phase 7.6: admin override for the dunning opt-out.
        // The opt-out is otherwise a tenant-only setting (the
        // tenant can flip it from the billing preferences page).
        // Admins need an escape hatch for: legal disputes where
        // the tenant has asked in writing to be opted back in,
        // an account recovery flow, or compliance audits.
        Route::put('/tenants/{tenant}/dunning-opt-out', [TenantsController::class, 'updateDunningOptOut'])->name('system.tenants.dunning-opt-out');
        // Phase 7.7: admin override to lift an auto-suspension
        // applied by dunning:auto-suspend. Different from a
        // manual un-suspend because the audit log distinguishes
        // the two.
        Route::post('/tenants/{tenant}/lift-auto-suspend', [TenantsController::class, 'liftAutoSuspend'])->name('system.tenants.lift-auto-suspend');
        Route::post('/tenants/{tenant}/extend-trial', [TenantsController::class, 'extendTrial'])->name('system.tenants.extend-trial');
        // Phase 7.5: per-tenant dunning config (overrides the
        // global config for this specific tenant). Useful for
        // high-touch customers who want different timing or
        // a different language for their reminders.
        Route::get('/tenants/{tenant}/dunning', [DunningController::class, 'tenantConfig'])->name('system.tenants.dunning');
        Route::put('/tenants/{tenant}/dunning', [DunningController::class, 'updateTenantConfig'])->name('system.tenants.dunning.update');
        Route::delete('/tenants/{tenant}/dunning', [DunningController::class, 'clearTenantConfig'])->name('system.tenants.dunning.clear');
        Route::delete('/tenants/{tenant}', [TenantsController::class, 'destroy'])->name('system.tenants.destroy');
        Route::put('/tenants/{tenant}/security', [TenantSecurityController::class, 'update2FASettings'])->name('system.tenants.security.update');
        // Impersonation is super_admin only — powerful debugging affordance.
        Route::post('/tenants/{tenant}/impersonate', [ImpersonationController::class, 'start'])->name('system.impersonate.start');

        // Bank Transfer Receipts Review
        Route::prefix('bank-transfer-receipts')->name('system.bank-receipts.')->group(function () {
            Route::get('/', [BankTransferReceiptsController::class, 'index'])->name('index');
            Route::get('/{receipt}', [BankTransferReceiptsController::class, 'show'])->name('show');
            Route::get('/{receipt}/download', [BankTransferReceiptsController::class, 'download'])->name('download');
            Route::post('/{receipt}/approve', [BankTransferReceiptsController::class, 'approve'])->name('approve');
            Route::post('/{receipt}/reject', [BankTransferReceiptsController::class, 'reject'])->name('reject');
        });

        // Tenant backups — super_admin only because they reveal
        // a tenant's full data set, and the file itself is held on
        // shared storage. Both pre-delete (automatic) and manual
        // backups go through this controller.
        Route::post('/tenants/{tenant}/backups', [BackupController::class, 'store'])->name('system.tenants.backups.store');
    });

    // Admin notification bell endpoints (Phase 4). Polling-based:
    // /unread-count every 30s + /recent on dropdown open. Both
    // available to any authenticated admin, not just super_admin,
    // because the queue/review work is a regular admin task.
    Route::prefix('notifications')->name('system.notifications.')->group(function () {
        Route::get('/recent', [AdminNotificationController::class, 'recent'])->name('recent');
        Route::get('/unread-count', [AdminNotificationController::class, 'unreadCount'])->name('unread-count');
        Route::post('/{notification}/read', [AdminNotificationController::class, 'markAsRead'])->name('read');
        Route::post('/read-all', [AdminNotificationController::class, 'markAllAsRead'])->name('read-all');
        // Full-page listing (optional — the dropdown is enough for most
        // cases, but a dedicated page helps when the list grows).
        Route::get('/', [AdminNotificationController::class, 'index'])->name('index');
    });

    // Backups cross-tenant listing/download — super_admin only.
    Route::middleware('super_admin')->group(function () {
        Route::get('/backups', [BackupController::class, 'index'])->name('system.backups.index');
        Route::get('/backups/{backup}/download', [BackupController::class, 'download'])->name('system.backups.download');
        Route::delete('/backups/{backup}', [BackupController::class, 'destroy'])->name('system.backups.destroy');
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

    // Dunning Settings (Phase 6 — collection automation)
    Route::get('/settings/dunning', [DunningController::class, 'settings'])->name('system.settings.dunning');
    Route::put('/settings/dunning', [DunningController::class, 'updateSettings'])->name('system.settings.dunning.update');
    Route::get('/billing/dunning-log', [DunningController::class, 'log'])->name('system.billing.dunning-log');

    // Company Settings
    Route::get('/settings/company', [CompanySettingsController::class, 'index'])->name('system.settings.company');
    Route::put('/settings/company', [CompanySettingsController::class, 'update'])->name('system.settings.company.update');
    Route::post('/settings/company/upload-logo', [CompanySettingsController::class, 'uploadLogo'])->name('system.settings.company.upload-logo');
    Route::delete('/settings/company/logo', [CompanySettingsController::class, 'removeLogo'])->name('system.settings.company.remove-logo');
    Route::post('/settings/company/upload-stamp', [CompanySettingsController::class, 'uploadStamp'])->name('system.settings.company.upload-stamp');
    Route::delete('/settings/company/stamp', [CompanySettingsController::class, 'removeStamp'])->name('system.settings.company.remove-stamp');

    // General Settings
    Route::get('/settings/general', [GeneralSettingsController::class, 'index'])->name('system.settings.general');
    Route::put('/settings/general', [GeneralSettingsController::class, 'update'])->name('system.settings.general.update');

    // Website Settings
    Route::get('/settings/website', [WebsiteSettingsController::class, 'index'])->name('system.settings.website');
    Route::put('/settings/website', [WebsiteSettingsController::class, 'update'])->name('system.settings.website.update');
    Route::post('/settings/website/upload-image', [WebsiteSettingsController::class, 'uploadImage'])->name('system.settings.website.upload-image');

    // Help & Knowledge Base Settings
    Route::get('/settings/help', [HelpSettingsController::class, 'index'])->name('system.settings.help');
    Route::put('/settings/help', [HelpSettingsController::class, 'update'])->name('system.settings.help.update');
    Route::post('/settings/help/reset', [HelpSettingsController::class, 'resetDefaults'])->name('system.settings.help.reset');
    Route::post('/settings/help/fetch-video-meta', [HelpSettingsController::class, 'fetchVideoMeta'])->name('system.settings.help.fetch_video_meta');
    Route::get('/help', fn () => redirect()->route('system.settings.help'))->name('system.help');

    // Contact Messages
    Route::get('/settings/contact-messages', [ContactMessageController::class, 'index'])->name('system.contact_messages.index');
    Route::put('/settings/contact-messages/{contactMessage}/read', [ContactMessageController::class, 'markAsRead'])->name('system.contact_messages.read');
    Route::delete('/settings/contact-messages/{contactMessage}', [ContactMessageController::class, 'destroy'])->name('system.contact_messages.destroy');

    // Feedbacks & Support Desk
    Route::prefix('settings/feedbacks')->name('system.feedbacks.')->group(function () {
        Route::get('/', [FeedbackManagementController::class, 'index'])->name('index');
        Route::post('/{feedback}/reply', [FeedbackManagementController::class, 'reply'])->name('reply');
        Route::put('/{feedback}/status', [FeedbackManagementController::class, 'updateStatus'])->name('update-status');
        Route::delete('/{feedback}', [FeedbackManagementController::class, 'destroy'])->name('destroy');
    });
    Route::get('/feedbacks', fn () => redirect()->route('system.feedbacks.index'));
    Route::get('/feedback', fn () => redirect()->route('system.feedbacks.index'));

    // Subscription Invoices
    Route::get('/invoices', [SubscriptionInvoicesController::class, 'index'])->name('system.invoices.index');
    Route::get('/invoices/{invoice}', [SubscriptionInvoicesController::class, 'show'])->name('system.invoices.show');
    Route::get('/invoices/{invoice}/download', [SubscriptionInvoicesController::class, 'download'])->name('system.invoices.download');
    Route::post('/invoices/{invoice}/send', [SubscriptionInvoicesController::class, 'send'])->name('system.invoices.send');
    Route::post('/invoices/{invoice}/mark-paid', [SubscriptionInvoicesController::class, 'markPaid'])->name('system.invoices.mark-paid');
    Route::post('/invoices/{invoice}/cancel', [SubscriptionInvoicesController::class, 'cancel'])->name('system.invoices.cancel');
    Route::post('/invoices/{invoice}/regenerate-pdf', [SubscriptionInvoicesController::class, 'regeneratePdf'])->name('system.invoices.regenerate-pdf');

    // Installments — removed (replaced by Tabby/Tamara BNPL)

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
    //
    // Creating, editing and deleting system-admin accounts is a
    // privileged operation: the role field selects what the account may
    // do inside the /system panel, and `super_admin` bypasses the
    // permission list entirely (AdminUser::hasPermission).
    //
    // These write routes used to sit in the general `system.admin`
    // group, so an account with the read-oriented `support` role could
    // POST a new `super_admin` account — or PUT its own role up to
    // `super_admin` — and take full control of the platform.
    //
    // The `super_admin` middleware closes that escalation path. The
    // index/edit routes stay readable by every system admin.
    Route::get('/admin-users', [AdminUsersController::class, 'index'])->name('system.admin-users.index');
    Route::get('/admin-users/create', [AdminUsersController::class, 'create'])->name('system.admin-users.create');
    Route::get('/activity-log', [AdminUsersController::class, 'activityLog'])->name('system.activity-log');

    Route::middleware('super_admin')->group(function () {
        Route::post('/admin-users', [AdminUsersController::class, 'store'])->name('system.admin-users.store');
        Route::get('/admin-users/{adminUser}/edit', [AdminUsersController::class, 'edit'])->name('system.admin-users.edit');
        Route::put('/admin-users/{adminUser}', [AdminUsersController::class, 'update'])->name('system.admin-users.update');
        Route::delete('/admin-users/{adminUser}', [AdminUsersController::class, 'destroy'])->name('system.admin-users.destroy');
    });

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
// Accepts either guard: the 'web' guard holds the tenant user we are
// impersonating, while the 'admin' guard still holds the system admin.
// Allowing both means the operator can always get back out, even if
// the tenant staff session expired while impersonating.
Route::post('/impersonate/stop', [ImpersonationController::class, 'stop'])
    ->name('impersonate.stop')
    ->middleware(['web', 'auth:web,admin']);

require __DIR__.'/auth.php';
