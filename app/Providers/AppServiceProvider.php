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
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // Defensive: invalidate spatie permission cache on every boot
        // so a stale cache (left over from before adding/removing permissions
        // or assigning roles) can never make super_admin bypass fail.
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

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
        Employee::observe(\App\Observers\EmployeeObserver::class);
        Center::observe(\App\Observers\CenterObserver::class);

        // Super Admin Bypass
        // Two auth models: User (web guard, spatie/permission) and
        // AdminUser (admin guard, role column). Both must be honored here —
        // AdminUser doesn't have HasRoles trait, so $user->hasRole() would
        // throw BadMethodCallException and break the gate entirely.
        Gate::before(function ($user, $ability) {
            if ($user instanceof AdminUser) {
                return $user->isSuperAdmin() ? true : null;
            }
            if ($user instanceof User) {
                return $user->hasRole('super_admin') ? true : null;
            }
            return null;
        });
    }
}
