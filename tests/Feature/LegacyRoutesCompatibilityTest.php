<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class LegacyRoutesCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach ([
            Permissions::CUSTOMERS_VIEW,
            Permissions::CUSTOMERS_CREATE,
            Permissions::CUSTOMERS_UPDATE,
            Permissions::SERVICES_VIEW,
            Permissions::SERVICES_UPDATE,
            Permissions::DEPARTMENTS_VIEW,
            Permissions::DEPARTMENTS_MANAGE,
        ] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
    }

    private function createTenantUser(Tenant $tenant, Center $center, array $permissions): User
    {
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
            'email_verified_at' => now(),
            'two_factor_confirmed_at' => now(),
        ]);

        $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);

        foreach ($permissions as $permission) {
            $user->givePermissionTo($permission);
        }

        return $user->refresh();
    }

    public function test_customers_create_redirects_authorized_user_and_forbids_unauthorized(): void
    {
        $tenant = Tenant::factory()->create();
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);

        $authorized = $this->createTenantUser($tenant, $center, [Permissions::CUSTOMERS_CREATE]);
        $unauthorized = $this->createTenantUser($tenant, $center, [Permissions::CUSTOMERS_VIEW]);

        $this->actingAs($authorized)
            ->get(route('customers.create'))
            ->assertRedirect(route('customers.index', ['create' => 1]));

        $this->actingAs($unauthorized)
            ->get(route('customers.create'))
            ->assertForbidden();
    }

    public function test_customers_edit_redirects_authorized_forbids_unauthorized_and_returns_404_cross_tenant(): void
    {
        $tenantA = Tenant::factory()->create();
        $centerA = Center::factory()->create(['tenant_id' => $tenantA->id]);
        $tenantB = Tenant::factory()->create();
        $centerB = Center::factory()->create(['tenant_id' => $tenantB->id]);

        $authorized = $this->createTenantUser($tenantA, $centerA, [Permissions::CUSTOMERS_UPDATE]);
        $unauthorized = $this->createTenantUser($tenantA, $centerA, [Permissions::CUSTOMERS_VIEW]);

        $customerA = Customer::factory()->create([
            'tenant_id' => $tenantA->id,
            'center_id' => $centerA->id,
        ]);

        $customerB = Customer::factory()->create([
            'tenant_id' => $tenantB->id,
            'center_id' => $centerB->id,
        ]);

        $this->actingAs($authorized)
            ->get(route('customers.edit', $customerA))
            ->assertRedirect(route('customers.show', ['customer' => $customerA, 'edit' => 1]));

        $this->actingAs($unauthorized)
            ->get(route('customers.edit', $customerA))
            ->assertForbidden();

        $this->actingAs($authorized)
            ->get(route('customers.edit', $customerB))
            ->assertNotFound();
    }

    public function test_services_and_departments_show_return_404_while_existing_routes_work(): void
    {
        $tenant = Tenant::factory()->create();
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);

        $user = $this->createTenantUser($tenant, $center, [
            Permissions::CUSTOMERS_VIEW,
            Permissions::SERVICES_VIEW,
            Permissions::SERVICES_UPDATE,
            Permissions::DEPARTMENTS_VIEW,
            Permissions::DEPARTMENTS_MANAGE,
        ]);

        $department = Department::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'name_ar' => 'قسم اختبار',
            'name_en' => 'Test Department',
            'is_active' => true,
        ]);

        $service = Service::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'department_id' => $department->id,
            'name_ar' => 'خدمة اختبار',
            'name_en' => 'Test Service',
            'price' => 100,
            'is_active' => true,
        ]);

        $customer = Customer::factory()->create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
        ]);

        $this->actingAs($user)->get("/app/services/{$service->id}")->assertStatus(405);
        $this->actingAs($user)->get("/app/departments/{$department->id}")->assertStatus(405);

        $this->actingAs($user)->get(route('customers.index'))->assertOk();
        $this->actingAs($user)->get(route('customers.show', $customer))->assertOk();
        $this->actingAs($user)->get(route('services.index'))->assertOk();
        $this->actingAs($user)->get(route('departments.index'))->assertOk();
        $this->actingAs($user)->patch("/app/services/{$service->id}/toggle-active")->assertRedirect();
        $this->actingAs($user)->patch("/app/departments/{$department->id}/toggle-active")->assertRedirect();
    }
}
