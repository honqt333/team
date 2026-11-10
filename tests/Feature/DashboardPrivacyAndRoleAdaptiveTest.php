<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Center;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Support\Permissions;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DashboardPrivacyAndRoleAdaptiveTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Center $center;

    protected function setUp(): void
    {
        parent::setUp();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create core permissions
        Permission::findOrCreate(Permissions::INVOICES_VIEW, 'web');
        Permission::findOrCreate(Permissions::WORK_ORDERS_VIEW, 'web');
        Permission::findOrCreate(Permissions::WORK_ORDERS_CREATE, 'web');
        Permission::findOrCreate(Permissions::CUSTOMERS_VIEW, 'web');
        Permission::findOrCreate(Permissions::VEHICLES_VIEW, 'web');

        $this->tenant = Tenant::factory()->create([
            'status' => 'active',
        ]);
        $this->center = Center::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
    }

    public function test_super_admin_receives_full_financial_metrics_and_permissions(): void
    {
        $superAdmin = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
        ]);
        $superAdmin->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);

        $role = Role::findOrCreate('super_admin', 'web');
        $superAdmin->assignRole($role);

        $customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
        ]);

        // Create an invoice
        Invoice::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'customer_id' => $customer->id,
            'type' => 'invoice',
            'status' => 'issued',
            'payment_status' => 'unpaid',
            'total_incl_tax' => 1500.00,
            'issue_date' => Carbon::now(),
        ]);

        $response = $this->actingAs($superAdmin)
            ->withSession(['current_center_id' => $this->center->id])
            ->get('/dashboard');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('userContext.canViewFinancials', true)
            ->where('stats.revenue.thisMonth', 1500)
            ->has('recentInvoices', 1)
        );
    }

    public function test_technician_does_not_receive_revenue_or_invoice_data(): void
    {
        $techUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
        ]);
        $techUser->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);

        $role = Role::findOrCreate('technician', 'web');
        $techUser->assignRole($role);

        $customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
        ]);

        // Sensitive financial records
        Invoice::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'customer_id' => $customer->id,
            'type' => 'invoice',
            'status' => 'issued',
            'payment_status' => 'unpaid',
            'total_incl_tax' => 9999.00,
            'issue_date' => Carbon::now(),
        ]);

        $response = $this->actingAs($techUser)
            ->withSession(['current_center_id' => $this->center->id])
            ->get('/dashboard');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('userContext.canViewFinancials', false)
            ->where('userContext.isTechnician', true)
            ->where('stats.revenue', null)
            ->has('recentInvoices', 0)
            ->has('topCustomers', 0)
        );
    }

    public function test_receptionist_does_not_receive_revenue_and_receives_today_appointments(): void
    {
        $receptionist = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
        ]);
        $receptionist->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);

        $role = Role::findOrCreate('receptionist', 'web');
        $receptionist->assignRole($role);

        $customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
        ]);

        $vehicle = Vehicle::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'customer_id' => $customer->id,
        ]);

        Appointment::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'scheduled_at' => Carbon::today()->addHours(10),
            'estimated_end_at' => Carbon::today()->addHours(11),
            'status' => 'confirmed',
            'source' => 'phone',
        ]);

        $response = $this->actingAs($receptionist)
            ->withSession(['current_center_id' => $this->center->id])
            ->get('/dashboard');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('userContext.canViewFinancials', false)
            ->where('userContext.isReceptionist', true)
            ->where('stats.revenue', null)
            ->has('todayAppointments', 1)
        );
    }
}
