<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PackagesCatalogCrossBranchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

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

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);

        foreach ($permissions as $permissionName) {
            $user->givePermissionTo($permissionName);
        }

        $user->refresh();

        return $user;
    }

    public function test_packages_catalog_scopes_local_packages_and_exposes_other_branches(): void
    {
        $tenant = Tenant::factory()->create();
        
        // Branch A
        $centerA = Center::factory()->create(['tenant_id' => $tenant->id]);
        $userA = $this->createUserWithPermissions(['services.view'], $tenant, $centerA);
        
        // Local service in branch A (child service)
        $serviceA = Service::factory()->create([
            'tenant_id' => $tenant->id,
            'center_id' => $centerA->id,
            'name_ar' => 'خدمة فرع أ',
            'name_en' => 'Service Branch A',
            'type' => 'internal',
        ]);

        // Local package in branch A
        $packageA = Service::factory()->create([
            'tenant_id' => $tenant->id,
            'center_id' => $centerA->id,
            'name_ar' => 'باقة فرع أ',
            'name_en' => 'Package Branch A',
            'type' => 'package',
        ]);
        $packageA->items()->attach($serviceA->id, ['quantity' => 2]);

        // Branch B
        $centerB = Center::factory()->create(['tenant_id' => $tenant->id]);
        
        // Service in branch B
        $serviceB = Service::factory()->create([
            'tenant_id' => $tenant->id,
            'center_id' => $centerB->id,
            'name_ar' => 'خدمة فرع ب',
            'name_en' => 'Service Branch B',
            'type' => 'internal',
        ]);

        // Package in branch B
        $packageB = Service::factory()->create([
            'tenant_id' => $tenant->id,
            'center_id' => $centerB->id,
            'name_ar' => 'باقة فرع ب',
            'name_en' => 'Package Branch B',
            'type' => 'package',
        ]);
        $packageB->items()->attach($serviceB->id, ['quantity' => 3]);

        // Branch of another tenant (should never be seen)
        $otherTenant = Tenant::factory()->create();
        $centerC = Center::factory()->create(['tenant_id' => $otherTenant->id]);
        $packageC = Service::factory()->create([
            'tenant_id' => $otherTenant->id,
            'center_id' => $centerC->id,
            'name_ar' => 'باقة مستأجر آخر',
            'name_en' => 'Package Other Tenant',
            'type' => 'package',
        ]);

        // Get index page as userA (Branch A)
        $response = $this->actingAs($userA)->get('/app/services');

        $response->assertOk();

        // 1. Verify Local Packages belong only to Branch A
        $packages = $response->viewData('page')['props']['packages'];
        $this->assertCount(1, $packages);
        $this->assertEquals($packageA->id, $packages[0]['id']);

        // 2. Verify Other Branches Packages catalog contains packageB but not packageA or packageC
        $otherBranchesPackages = $response->viewData('page')['props']['otherBranchesPackages'];
        $this->assertCount(1, $otherBranchesPackages);
        $this->assertEquals($packageB->name_ar, $otherBranchesPackages[0]['name_ar']);
        
        // Assert other tenant's package is isolated
        $this->assertEmpty(
            collect($otherBranchesPackages)->filter(fn($p) => $p['name_ar'] === $packageC->name_ar)
        );

        // Verify items inside packageB are loaded
        $this->assertCount(1, $otherBranchesPackages[0]['items']);
        $this->assertEquals(3, $otherBranchesPackages[0]['items'][0]['quantity']);
    }

    public function test_user_can_clone_package_from_other_branch_catalog_with_custom_pricing(): void
    {
        $tenant = Tenant::factory()->create();
        
        // Branch A
        $centerA = Center::factory()->create(['tenant_id' => $tenant->id]);
        $userA = $this->createUserWithPermissions(['services.view', 'services.create'], $tenant, $centerA);
        
        $serviceA = Service::factory()->create([
            'tenant_id' => $tenant->id,
            'center_id' => $centerA->id,
            'name_ar' => 'خدمة مشتركة',
            'name_en' => 'Shared Service',
            'type' => 'internal',
        ]);

        // Branch B
        $centerB = Center::factory()->create(['tenant_id' => $tenant->id]);
        $packageB = Service::factory()->create([
            'tenant_id' => $tenant->id,
            'center_id' => $centerB->id,
            'name_ar' => 'باقة مميزة',
            'name_en' => 'Premium Package',
            'description_ar' => 'وصف الباقة المميزة',
            'description_en' => 'Premium package description',
            'type' => 'package',
        ]);
        $packageB->items()->attach($serviceA->id, ['quantity' => 1]);

        // Now post a new package under centerA with packageB details, but branchA-specific pricing
        $response = $this->actingAs($userA)->postJson('/app/services', [
            'name_ar' => $packageB->name_ar,
            'name_en' => $packageB->name_en,
            'description_ar' => $packageB->description_ar,
            'description_en' => $packageB->description_en,
            'type' => 'package',
            'items' => [
                ['id' => $serviceA->id, 'quantity' => 1]
            ],
            // Branch A specific rates:
            'base_price' => 800.00,
            'min_price' => 700.00,
            'default_discount_type' => 'none',
            'default_discount_value' => null,
            'allow_price_override' => true,
            'is_active' => true,
        ]);

        $response->assertRedirect();
        
        // Assert a new package row was created for Center A
        $this->assertDatabaseHas('services', [
            'center_id' => $centerA->id,
            'name_ar' => 'باقة مميزة',
            'type' => 'package',
            'base_price' => 800.00,
            'min_price' => 700.00,
        ]);

        $newPackage = Service::where('center_id', $centerA->id)->where('name_ar', 'باقة مميزة')->first();
        $this->assertNotNull($newPackage);
        $this->assertCount(1, $newPackage->items);
        $this->assertEquals(1, $newPackage->items->first()->pivot->quantity);
    }

    public function test_user_can_create_new_package_definition_with_zero_price(): void
    {
        $tenant = Tenant::factory()->create();
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);
        $user = $this->createUserWithPermissions(['services.view', 'services.create'], $tenant, $center);

        $service = Service::factory()->create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'type' => 'internal',
        ]);

        $response = $this->actingAs($user)->postJson('/app/services', [
            'name_ar' => 'تعريف باقة جديدة',
            'name_en' => 'New Package Def',
            'description_ar' => 'وصف تعريف باقة جديدة',
            'description_en' => 'New Package Def desc',
            'type' => 'package',
            'items' => [
                ['id' => $service->id, 'quantity' => 1]
            ],
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
            'name_ar' => 'تعريف باقة جديدة',
            'type' => 'package',
            'base_price' => 0.00,
            'min_price' => 0.00,
        ]);
    }
}
