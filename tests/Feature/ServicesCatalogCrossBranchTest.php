<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Department;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ServicesCatalogCrossBranchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Reset permission cache
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create required permissions for services
        Permission::findOrCreate('services.view', 'web');
        Permission::findOrCreate('services.create', 'web');
        Permission::findOrCreate('services.update', 'web');
        Permission::findOrCreate('services.delete', 'web');
        Permission::findOrCreate('services.departments.view', 'web');
        Permission::findOrCreate('services.departments.manage', 'web');
    }

    protected function createUserWithPermissions(array $permissions, ?Tenant $tenant = null, ?Center $center = null): User
    {
        $tenant = $tenant ?? Tenant::factory()->create();
        $center = $center ?? Center::factory()->create(['tenant_id' => $tenant->id]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
        ]);

        $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);

        // Set the team id for permissions based on the user's tenant
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);

        foreach ($permissions as $permissionName) {
            $user->givePermissionTo($permissionName);
        }

        $user->refresh();

        return $user;
    }

    public function test_services_catalog_scopes_local_services_and_exposes_other_branches(): void
    {
        $tenant = Tenant::factory()->create();
        
        // Branch A
        $centerA = Center::factory()->create(['tenant_id' => $tenant->id]);
        $userA = $this->createUserWithPermissions(['services.view'], $tenant, $centerA);
        $deptA = Department::create(['tenant_id' => $tenant->id, 'center_id' => $centerA->id, 'name_ar' => 'قسم أ', 'name_en' => 'Dept A']);
        $serviceA = Service::factory()->create([
            'tenant_id' => $tenant->id,
            'center_id' => $centerA->id,
            'department_id' => $deptA->id,
            'name_ar' => 'خدمة فرع أ',
            'name_en' => 'Service Branch A',
        ]);

        // Branch B
        $centerB = Center::factory()->create(['tenant_id' => $tenant->id]);
        $deptB = Department::create(['tenant_id' => $tenant->id, 'center_id' => $centerB->id, 'name_ar' => 'قسم ب', 'name_en' => 'Dept B']);
        $serviceB = Service::factory()->create([
            'tenant_id' => $tenant->id,
            'center_id' => $centerB->id,
            'department_id' => $deptB->id,
            'name_ar' => 'خدمة فرع ب',
            'name_en' => 'Service Branch B',
        ]);

        // Branch of another tenant (should never be seen)
        $otherTenant = Tenant::factory()->create();
        $centerC = Center::factory()->create(['tenant_id' => $otherTenant->id]);
        $serviceC = Service::factory()->create([
            'tenant_id' => $otherTenant->id,
            'center_id' => $centerC->id,
            'name_ar' => 'خدمة مستأجر آخر',
            'name_en' => 'Service Other Tenant',
        ]);

        // Get index page as userA (Branch A)
        $response = $this->actingAs($userA)->get('/app/services');

        $response->assertOk();

        // 1. Verify Local Services belong only to Branch A
        $departments = $response->viewData('page')['props']['departments'];
        $this->assertCount(1, $departments);
        $this->assertEquals($deptA->id, $departments[0]['id']);
        $this->assertCount(1, $departments[0]['services']);
        $this->assertEquals($serviceA->id, $departments[0]['services'][0]['id']);

        // 2. Verify Other Branches Services catalog contains serviceB but not serviceA or serviceC
        $otherBranchesServices = $response->viewData('page')['props']['otherBranchesServices'];
        $this->assertCount(1, $otherBranchesServices);
        $this->assertEquals($serviceB->name_ar, $otherBranchesServices[0]['name_ar']);
        
        // Assert other tenant's service is isolated
        $this->assertEmpty(
            collect($otherBranchesServices)->filter(fn($s) => $s['name_ar'] === $serviceC->name_ar)
        );
    }

    public function test_user_can_clone_service_from_other_branch_catalog_with_custom_pricing(): void
    {
        $tenant = Tenant::factory()->create();
        
        // Branch A
        $centerA = Center::factory()->create(['tenant_id' => $tenant->id]);
        $userA = $this->createUserWithPermissions(['services.view', 'services.create'], $tenant, $centerA);
        $deptA = Department::create(['tenant_id' => $tenant->id, 'center_id' => $centerA->id, 'name_ar' => 'قسم أ', 'name_en' => 'Dept A']);

        // Branch B
        $centerB = Center::factory()->create(['tenant_id' => $tenant->id]);
        $deptB = Department::create(['tenant_id' => $tenant->id, 'center_id' => $centerB->id, 'name_ar' => 'قسم ب', 'name_en' => 'Dept B']);
        $serviceB = Service::factory()->create([
            'tenant_id' => $tenant->id,
            'center_id' => $centerB->id,
            'department_id' => $deptB->id,
            'name_ar' => 'تلميع ساطع',
            'name_en' => 'Polishing',
            'description_ar' => 'تلميع كامل للمركبة',
            'description_en' => 'Full vehicle polishing',
            'duration_value' => 120,
            'duration_unit' => 'minutes',
            'warranty_value' => 3,
            'warranty_unit' => 'months',
            'type' => 'internal',
        ]);

        // Now post a new service under centerA with serviceB details, but branchA-specific prices
        $response = $this->actingAs($userA)->postJson('/app/services', [
            'department_id' => $deptA->id,
            'name_ar' => $serviceB->name_ar,
            'name_en' => $serviceB->name_en,
            'description_ar' => $serviceB->description_ar,
            'description_en' => $serviceB->description_en,
            'duration_value' => $serviceB->duration_value,
            'duration_unit' => $serviceB->duration_unit,
            'warranty_value' => $serviceB->warranty_value,
            'warranty_unit' => $serviceB->warranty_unit,
            'type' => $serviceB->type,
            // Branch A specific rates:
            'base_price' => 500.00,
            'min_price' => 450.00,
            'default_discount_type' => 'percentage',
            'default_discount_value' => 10.00,
            'allow_price_override' => true,
            'is_active' => true,
        ]);

        $response->assertRedirect();
        
        // Assert a new service row was created for Center A
        $this->assertDatabaseHas('services', [
            'center_id' => $centerA->id,
            'name_ar' => 'تلميع ساطع',
            'base_price' => 500.00,
            'min_price' => 450.00,
            'default_discount_type' => 'percentage',
            'default_discount_value' => 10.00,
        ]);
    }

    public function test_user_can_create_new_service_definition_with_zero_price(): void
    {
        $tenant = Tenant::factory()->create();
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);
        $user = $this->createUserWithPermissions(['services.view', 'services.create'], $tenant, $center);
        $dept = Department::create(['tenant_id' => $tenant->id, 'center_id' => $center->id, 'name_ar' => 'قسم أ', 'name_en' => 'Dept A']);

        // Creating a new service definition (without pricing) posts base_price: 0
        $response = $this->actingAs($user)->postJson('/app/services', [
            'department_id' => $dept->id,
            'name_ar' => 'خدمة جديدة بالكامل',
            'name_en' => 'Brand New Service',
            'description_ar' => 'تفاصيل جديدة',
            'description_en' => 'New details',
            'duration_value' => 60,
            'duration_unit' => 'minutes',
            'warranty_value' => 1,
            'warranty_unit' => 'years',
            'type' => 'internal',
            'base_price' => 0,
            'min_price' => 0,
            'default_discount_type' => 'none',
            'default_discount_value' => null,
            'allow_price_override' => false,
            'is_active' => true,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('services', [
            'center_id' => $center->id,
            'name_ar' => 'خدمة جديدة بالكامل',
            'base_price' => 0.00,
            'min_price' => 0.00,
        ]);
    }
}
