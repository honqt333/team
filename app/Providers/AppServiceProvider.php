<?php

namespace App\Providers;

use App\Models\AdminUser;
use App\Models\Center;
use App\Models\Customer;
use App\Models\Department;
use App\Models\HR\Attendance;
use App\Models\HR\Employee;
use App\Models\HR\EmployeeContract;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturnInvoice;
use App\Models\Quote;
use App\Models\QuoteLine;
use App\Models\Service;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleColor;
use App\Models\VehicleMake;
use App\Models\VehicleModel;
use App\Models\WorkOrder;
use App\Models\WorkOrderInspection;
use App\Observers\CenterObserver;
use App\Observers\EmployeeObserver;
use App\Policies\CustomerPolicy;
use App\Policies\DepartmentPolicy;
use App\Policies\EmployeeContractPolicy;
use App\Policies\HR\AttendancePolicy;
use App\Policies\HR\EmployeePolicy;
use App\Policies\PurchaseInvoicePolicy;
use App\Policies\QuoteLinePolicy;
use App\Policies\QuotePolicy;
use App\Policies\ServicePolicy;
use App\Policies\SupplierPolicy;
use App\Policies\VehicleColorPolicy;
use App\Policies\VehicleMakePolicy;
use App\Policies\VehicleModelPolicy;
use App\Policies\VehiclePolicy;
use App\Policies\WorkOrderInspectionPolicy;
use App\Policies\WorkOrderPolicy;
use App\Services\Email\SmtpConfigService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\PermissionRegistrar;

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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

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
        if (Schema::hasTable('cache')) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }

        // Register policies
        Gate::policy(Customer::class, CustomerPolicy::class);
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

        // HR Policies
        Gate::policy(Employee::class, EmployeePolicy::class);
        Gate::policy(Attendance::class, AttendancePolicy::class);
        Gate::policy(EmployeeContract::class, EmployeeContractPolicy::class);

        // Purchasing Policies
        // PurchaseInvoicePolicy covers both PurchaseInvoice and PurchaseReturnInvoice
        Gate::policy(PurchaseInvoice::class, PurchaseInvoicePolicy::class);
        Gate::policy(PurchaseReturnInvoice::class, PurchaseInvoicePolicy::class);

        // Work Order Inspection Policy
        Gate::policy(WorkOrderInspection::class, WorkOrderInspectionPolicy::class);

        // Observers
        Employee::observe(EmployeeObserver::class);
        Center::observe(CenterObserver::class);

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
}
