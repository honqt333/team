<?php

namespace App\Providers;

use App\Models\Customer;
use App\Models\Department;
use App\Models\Quote;
use App\Models\QuoteLine;
use App\Models\Service;
use App\Models\Vehicle;
use App\Models\VehicleColor;
use App\Models\VehicleMake;
use App\Models\VehicleModel;
use App\Models\WorkOrder;
use App\Policies\CustomerPolicy;
use App\Policies\DepartmentPolicy;
use App\Policies\QuoteLinePolicy;
use App\Policies\QuotePolicy;
use App\Policies\ServicePolicy;
use App\Policies\VehicleColorPolicy;
use App\Policies\VehicleMakePolicy;
use App\Policies\VehicleModelPolicy;
use App\Policies\VehiclePolicy;
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
    }
}
