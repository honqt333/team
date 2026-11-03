<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\CustomerPortalOtpChannel;
use App\Models\AdminUser;
use App\Models\AppointmentMessageDelivery;
use App\Models\Center;
use App\Models\CompanyTransaction;
use App\Models\Customer;
use App\Models\Department;
use App\Models\HR\Attendance;
use App\Models\HR\Employee;
use App\Models\HR\EmployeeContract;
use App\Models\HR\EmployeeDocument;
use App\Models\HR\Leave;
use App\Models\HR\Payroll;
use App\Models\Integration\Integration;
use App\Models\Payment;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturnInvoice;
use App\Models\Quote;
use App\Models\QuoteLine;
use App\Models\Service;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleColor;
use App\Models\VehicleMake;
use App\Models\VehicleModel;
use App\Models\WorkOrder;
use App\Models\WorkOrderInspection;
use App\Models\WorkOrderItem;
use App\Observers\CenterObserver;
use App\Observers\EmployeeObserver;
use App\Observers\PaymentObserver;
use App\Observers\WorkOrderItemObserver;
use App\Observers\WorkOrderObserver;
use App\Policies\AppointmentMessageDeliveryPolicy;
use App\Policies\CompanyTransactionPolicy;
use App\Policies\CustomerPolicy;
use App\Policies\DepartmentPolicy;
use App\Policies\EmployeeContractPolicy;
use App\Policies\HR\AttendancePolicy;
use App\Policies\HR\EmployeeDocumentPolicy;
use App\Policies\HR\EmployeePolicy;
use App\Policies\LeavePolicy;
use App\Policies\PaymentPolicy;
use App\Policies\PayrollPolicy;
use App\Policies\PurchaseInvoicePolicy;
use App\Policies\QuoteLinePolicy;
use App\Policies\QuotePolicy;
use App\Policies\ServicePolicy;
use App\Policies\SupplierPolicy;
use App\Policies\TenantPolicy;
use App\Policies\VehicleColorPolicy;
use App\Policies\VehicleMakePolicy;
use App\Policies\VehicleModelPolicy;
use App\Policies\VehiclePolicy;
use App\Policies\WorkOrderInspectionPolicy;
use App\Policies\WorkOrderItemPolicy;
use App\Policies\WorkOrderPolicy;
use App\Services\CustomerPortal\AuthenticaCustomerPortalOtpChannel;
use App\Services\CustomerPortal\CustomerPortalOtpService;
use App\Services\CustomerPortal\LoggingCustomerPortalOtpChannel;
use App\Services\CustomerPortal\SystemSmsOtpChannel;
use App\Services\Email\SmtpConfigService;
use App\Services\Messaging\NullSmsService;
use App\Services\Messaging\SmsService;
use Closure;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\PermissionRegistrar;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Make SmtpConfigService a singleton so we look up the default email
        // integration once per request and reuse the resolved config.
        $this->app->singleton(SmtpConfigService::class);

        // Phase 10 — SmsService binding.
        //
        // In testing we register a NullSmsService that
        // returns success without calling any real
        // provider. This lets every existing test in
        // the suite (Phase 7/8/9) run untouched
        // even though the controller paths now invoke
        // the dispatcher. Tests that need to assert
        // on the SmsService rebind it via
        // `$this->app->instance(SmsService::class, ...)`.
        $this->app->singleton(SmsService::class, function ($app) {
            $env = (string) (env('APP_ENV') ?? getenv('APP_ENV') ?: '');

            if ($env === 'testing') {
                return new NullSmsService;
            }

            return new SmsService;
        });

        // Phase 7 — Customer portal OTP channel binding.
        //
        // The default channel is the logging channel: it
        // records each OTP send in the structured log
        // (operator can grep "customer_portal.otp.sent") and
        // returns success. Real deployments swap this for a
        // gateway-backed implementation in
        // config('customer_portal.channel') or by re-binding
        // \App\Contracts\CustomerPortalOtpChannel.
        //
        // Phase 10 — the binding now also takes the
        // `CUSTOMER_PORTAL_OTP_CHANNEL` env var. In
        // production (`APP_ENV !== testing`) the binding
        // is the SystemSmsOtpChannel, which wraps the
        // existing SmsService (Integration model in
        // /system/integrations). In testing we keep the
        // logging channel so unit tests do not need a
        // real provider configured.
        //
        // The default for `local` is also SystemSmsOtpChannel
        // (the developer can flip the env var back to
        // `logging` if they don't have an SMS provider
        // configured). The OTP service and the controllers
        // do not know which implementation is active.
        //
        // CustomerPortalOtpService is a singleton because it
        // is constructed once per request and the channel
        // binding is stable; the per-request work (rate
        // limiting, hashing, lookups) lives inside the
        // service's methods, not in its state.
        $channel = $this->resolveOtpChannel();
        $this->app->singleton(
            CustomerPortalOtpChannel::class,
            $channel
        );
        $this->app->singleton(CustomerPortalOtpService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // Register the Amiri Arabic font with DomPDF so PDF
        // generation can render Arabic text correctly. Without
        // a font that has Arabic glyphs + shaping tables (GPOS /
        // GSUB), DomPDF prints every character in left-to-right
        // source order — the result is "ورب ةمدخ" instead of
        // "خدمة برو". The font files ship in resources/fonts/
        // (Amiri-Regular.ttf, Amiri-Bold.ttf) and are committed
        // so the same fix works on macOS, Linux, and Windows
        // without any system font installation.
        $this->registerArabicFontForDomPDF();

        // Apply SMTP from the default email integration (DB-driven) before
        // any notification or email gets a chance to read mail.* config.
        // Both development and production read the same source of truth,
        // configured under /system/integrations.
        app(SmtpConfigService::class)->apply();

        // Defensive: invalidate spatie permission cache on every boot
        // so a stale cache (left over from before adding/removing permissions
        // or assigning roles) can never make super_admin bypass fail.
        //
        // The cache table may not exist yet on a fresh install (pre-migrate)
        // or in a unit-test environment that has not loaded the schema. The
        // forget call would otherwise throw a QueryException at boot, which
        // would block any artisan command that runs before migrations — most
        // importantly `php artisan key:generate` in CI. Bail out cleanly
        // when the table is missing; the next request after migrations will
        // pick up the live cache.
        try {
            if (Schema::hasTable('cache')) {
                app(PermissionRegistrar::class)->forgetCachedPermissions();
            }
        } catch (Throwable $e) {
            // Silently fail if DB is not ready or restricted during boot
        }

        // Register policies
        Gate::policy(Customer::class, CustomerPolicy::class);
        Gate::policy(CompanyTransaction::class, CompanyTransactionPolicy::class);
        Gate::policy(Vehicle::class, VehiclePolicy::class);
        Gate::policy(WorkOrder::class, WorkOrderPolicy::class);
        Gate::policy(Department::class, DepartmentPolicy::class);
        Gate::policy(Service::class, ServicePolicy::class);
        Gate::policy(VehicleMake::class, VehicleMakePolicy::class);
        Gate::policy(VehicleModel::class, VehicleModelPolicy::class);
        Gate::policy(VehicleColor::class, VehicleColorPolicy::class);
        Gate::policy(Quote::class, QuotePolicy::class);
        Gate::policy(QuoteLine::class, QuoteLinePolicy::class);
        Gate::policy(Supplier::class, SupplierPolicy::class);
        Gate::policy(Tenant::class, TenantPolicy::class);

        // HR Policies
        Gate::policy(Employee::class, EmployeePolicy::class);
        Gate::policy(Attendance::class, AttendancePolicy::class);
        Gate::policy(EmployeeContract::class, EmployeeContractPolicy::class);
        Gate::policy(EmployeeDocument::class, EmployeeDocumentPolicy::class);

        // Purchasing Policies
        // PurchaseInvoicePolicy covers both PurchaseInvoice and PurchaseReturnInvoice
        Gate::policy(PurchaseInvoice::class, PurchaseInvoicePolicy::class);
        Gate::policy(PurchaseReturnInvoice::class, PurchaseInvoicePolicy::class);

        // Work Order Inspection Policy
        Gate::policy(WorkOrderInspection::class, WorkOrderInspectionPolicy::class);

        // Phase 0 Critical Policies
        Gate::policy(Payment::class, PaymentPolicy::class);
        Gate::policy(Leave::class, LeavePolicy::class);
        Gate::policy(Payroll::class, PayrollPolicy::class);
        Gate::policy(WorkOrderItem::class, WorkOrderItemPolicy::class);

        // Phase 4: appointments + service bays
        Gate::policy(Appointment::class, AppointmentPolicy::class);
        Gate::policy(ServiceBay::class, ServiceBayPolicy::class);

        // Phase 11: appointment message delivery log
        // (the Phase 10 deliveries table, surfaced
        // to the staff UI for retry).
        Gate::policy(
            AppointmentMessageDelivery::class,
            AppointmentMessageDeliveryPolicy::class
        );

        // Observers
        Employee::observe(EmployeeObserver::class);
        Center::observe(CenterObserver::class);
        WorkOrder::observe(WorkOrderObserver::class);
        WorkOrderItem::observe(WorkOrderItemObserver::class);
        Payment::observe(PaymentObserver::class);

        // Super Admin Bypass
        //
        // Two auth models: User (web guard, spatie/permission) and
        // AdminUser (admin guard, role column).
        //
        // SECURITY: We never bypass on the tenant-level `super_admin`
        // role — that role is granted to every new signup in
        // RegisteredUserController and a Gate::before bypass would be
        // a privilege-escalation vector. Real system admins still
        // bypass:
        //   - AdminUser with is_super_admin = true
        //   - User with is_system_admin = true
        // Tenant owners have all permissions through the role itself
        // (see TenantSetupService::getDefaultRoles), so a policy
        // bypass is unnecessary for their day-to-day work.
        Gate::before(function ($user, $ability) {
            if ($user instanceof AdminUser) {
                return $user->isSuperAdmin() ? true : null;
            }

            if ($user instanceof User && $user->is_system_admin) {
                return true;
            }

            return null;
        });
    }

    /**
     * Register the Amiri Arabic font with DomPDF so PDF
     * generation can render Arabic text correctly. Without a
     * font that has Arabic glyphs + shaping tables, DomPDF
     * prints every character in left-to-right source order —
     * the result is "ورب ةمدخ" instead of "خدمة برو".
     *
     * We commit the TTF files in resources/fonts/ (Amiri is
     * SIL OFL licensed, redistributable) so the same fix
     * works on macOS, Linux, and Windows without requiring
     * any system font installation.
     *
     * Idempotent — DomPDF silently ignores re-registration
     * of the same font family, so calling this on every
     * boot is safe.
     */
    /**
     * Pick the OTP channel class for this environment.
     *
     * Precedence:
     *   1. CUSTOMER_PORTAL_OTP_CHANNEL env var (if set,
     *      operator can force a specific channel — useful
     *      for staging). Accepts 'logging', 'system', or
     *      'authentica'.
     *   2. APP_ENV === 'testing' → LoggingCustomerPortalOtpChannel
     *      (so the existing Phase 7/8/9 test suite that
     *      greps "customer_portal.otp.sent" still works).
     *   3. Otherwise, if the active default SMS integration
     *      is `authentica` (and is configured), bind
     *      AuthenticaCustomerPortalOtpChannel. Authentica v2
     *      has NO general "send free-form SMS" endpoint, so
     *      it cannot be used by SmsService; the OTP-only
     *      channel talks directly to /send-otp with our
     *      own pre-generated 6-digit code.
     *   4. Otherwise → SystemSmsOtpChannel (production-
     *      grade, talks to the configured SMS provider
     *      via SmsService + Integration table).
     *
     * Returns a Closure (not a class string) so the
     * actual class is decided at resolve time, after
     * the framework is fully booted. The previous
     * implementation returned a class name, but with
     * the Authentica branch we have to query the DB
     * to read the default integration, and the DB
     * connection is not yet available during the
     * `register()` phase. The Closure defers that
     * lookup until the first `app(CustomerPortalOtpChannel::class)`
     * call.
     */
    private function resolveOtpChannel(): Closure
    {
        $forced = env('CUSTOMER_PORTAL_OTP_CHANNEL');

        if (is_string($forced) && $forced !== '') {
            return match ($forced) {
                'logging' => fn () => app(LoggingCustomerPortalOtpChannel::class),
                'system' => fn () => app(SystemSmsOtpChannel::class),
                'authentica' => function () {
                    // The operator explicitly asked for
                    // Authentica. If the default SMS
                    // integration is authentica AND
                    // configured, use the Authentica
                    // channel. Otherwise fall back to
                    // the system channel — a generic
                    // "send via configured provider"
                    // path that respects whatever SMS
                    // provider the tenant has wired
                    // up. Falling back to the logging
                    // channel would silently swallow
                    // every OTP send.
                    $channel = $this->resolveAuthenticaOtpChannel();

                    return $channel ?? app(SystemSmsOtpChannel::class);
                },
                default => fn () => app(LoggingCustomerPortalOtpChannel::class),
            };
        }

        // APP_ENV is set by phpunit.xml to "testing"
        // (force=true). We also check getenv() because
        // some CI runners set it on the OS side and
        // phpunit.xml is not used.
        $env = (string) (env('APP_ENV') ?? getenv('APP_ENV') ?: '');

        if ($env === 'testing') {
            return fn () => app(LoggingCustomerPortalOtpChannel::class);
        }

        // Lazy resolve. The Authentica lookup runs
        // at the moment the first
        // CustomerPortalOtpChannel is requested, by
        // which point the DB connection is ready.
        return function () {
            $channel = $this->resolveAuthenticaOtpChannel();

            if ($channel !== null) {
                return $channel;
            }

            return app(SystemSmsOtpChannel::class);
        };
    }

    /**
     * Build the AuthenticaCustomerPortalOtpChannel
     * from the default SMS integration, or null if the
     * default integration is not Authentica (or not
     * configured). The lookup is read at provider-
     * resolve time; a freshly-edited default integration
     * takes effect on the next container resolution.
     */
    private function resolveAuthenticaOtpChannel(): ?AuthenticaCustomerPortalOtpChannel
    {
        // The integration lookup may fail if the DB
        // is not yet migrated, or if the integrations
        // table is not part of the current schema.
        // In that case we fall back to the system
        // channel — never the Authentica one, which
        // requires a configured integration.
        try {
            $integration = Integration::getDefault('sms');
        } catch (Throwable $e) {
            return null;
        }

        if ($integration === null || $integration->provider !== 'authentica') {
            return null;
        }

        if (! $integration->isConfigured()) {
            return null;
        }

        return new AuthenticaCustomerPortalOtpChannel($integration);
    }

    private function registerArabicFontForDomPDF(): void
    {
        $fontDir = base_path('resources/fonts');

        if (! is_dir($fontDir)) {
            return;
        }

        // Barryvdh's `dompdf.options` binding only reads
        // `dompdf.options` config (not the top-level
        // `dompdf.font_dir` / `dompdf.font_cache`), so we have to
        // patch BOTH places. We also patch the top-level keys
        // for any third-party code that reads them directly.
        config([
            'dompdf.font_dir' => array_values(array_unique(array_merge(
                (array) config('dompdf.font_dir', []),
                [$fontDir],
            ))),
            // The default config points at storage_path('fonts')
            // which does not exist out of the box. resources/fonts/
            // is already writable (we commit TTFs there) so it's
            // the safer default.
            'dompdf.font_cache' => $fontDir,

            // Critical: dompdf.options must be a single string
            // (DomPDF\Options takes `fontDir` and `fontCache` as
            // scalars, not arrays). We pass the resources/fonts
            // dir directly.
            'dompdf.options' => array_merge(
                (array) config('dompdf.options', []),
                [
                    'fontDir' => $fontDir,
                    'fontCache' => $fontDir,
                ],
            ),
        ]);

        // Force the next wrapper call to read the updated
        // dompdf.options. The service is `bind`, not singleton,
        // so each `app('dompdf.wrapper')` resolves fresh, but
        // the underlying `dompdf.options` is cached as a
        // singleton on first resolve — so we forget it.
        $this->app->forgetInstance('dompdf.options');
        $this->app->forgetInstance('dompdf');
        $this->app->forgetInstance('dompdf.wrapper');
    }
}
