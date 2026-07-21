<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SuppliersIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Reset permission cache
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Seed all permissions from the registry so that TenantSetupService role generation succeeds
        $this->seed(PermissionsSeeder::class);
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

        // Assign permissions to user
        foreach ($permissions as $permissionName) {
            $user->givePermissionTo($permissionName);
        }

        // Refresh user to load permissions
        $user->refresh();

        return $user;
    }

    public function test_authorized_user_can_create_and_list_suppliers_within_center(): void
    {
        $user = $this->createUserWithPermissions([
            'purchasing.suppliers.view',
            'purchasing.suppliers.create',
        ]);

        // Create supplier
        $response = $this->actingAs($user)->post('/app/purchasing/suppliers', [
            'name' => 'Supplier A',
            'type' => 'parts',
            'contact_person' => 'Jane Doe',
            'phone' => '+966501234567',
        ]);

        $response->assertRedirect();

        // Verify supplier was created with correct tenant/center
        $this->assertDatabaseHas('suppliers', [
            'name' => 'Supplier A',
            'tenant_id' => $user->tenant_id,
            'center_id' => $user->current_center_id,
        ]);

        // List suppliers and verify it appears
        $listResponse = $this->actingAs($user)->get('/app/purchasing/suppliers');
        $listResponse->assertStatus(200);

        // Assert supplier is in the returned Inertia props
        $pageSuppliers = $listResponse->original->getData()['page']['props']['suppliers']['data'];
        $this->assertCount(1, $pageSuppliers);
        $this->assertEquals('Supplier A', $pageSuppliers[0]['name']);
    }

    public function test_user_cannot_access_supplier_from_another_center(): void
    {
        // User in Center A
        $userA = $this->createUserWithPermissions(['purchasing.suppliers.view']);

        // Create Tenant/Center B and a supplier belonging to it
        $tenantB = Tenant::factory()->create();
        $centerB = Center::factory()->create(['tenant_id' => $tenantB->id]);
        $supplierB = Supplier::create([
            'tenant_id' => $tenantB->id,
            'center_id' => $centerB->id,
            'name' => 'Supplier Center B',
            'type' => 'parts',
            'contact_person' => 'Bob Smith',
        ]);

        // Try to access Supplier B using User A
        $response = $this->actingAs($userA)->get("/app/purchasing/suppliers/{$supplierB->id}");

        // Since CenterScoped is active, Route Model Binding will fail to find it, returning 404
        $response->assertStatus(404);
    }

    public function test_user_cannot_update_supplier_from_another_center(): void
    {
        // User in Center A
        $userA = $this->createUserWithPermissions(['purchasing.suppliers.update']);

        // Create Tenant/Center B and a supplier belonging to it
        $tenantB = Tenant::factory()->create();
        $centerB = Center::factory()->create(['tenant_id' => $tenantB->id]);
        $supplierB = Supplier::create([
            'tenant_id' => $tenantB->id,
            'center_id' => $centerB->id,
            'name' => 'Supplier Center B',
            'type' => 'parts',
            'contact_person' => 'Bob Smith',
        ]);

        // Try to update Supplier B using User A
        $response = $this->actingAs($userA)->put("/app/purchasing/suppliers/{$supplierB->id}", [
            'name' => 'Hacked Supplier Name',
            'type' => 'parts',
            'contact_person' => 'Bob Smith',
        ]);

        // Route Model Binding fails due to CenterScoped global scope
        $response->assertStatus(404);

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplierB->id,
            'name' => 'Supplier Center B', // Name should not be updated
        ]);
    }

    public function test_supplier_tax_number_validation(): void
    {
        $user = $this->createUserWithPermissions([
            'purchasing.suppliers.create',
        ]);

        // 1. Valid 15-digit tax number passes
        $response = $this->actingAs($user)->post('/app/purchasing/suppliers', [
            'name' => 'Supplier A',
            'type' => 'parts',
            'contact_person' => 'Jane Doe',
            'phone' => '+966501234567',
            'tax_number' => '123456789012345',
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('suppliers', [
            'name' => 'Supplier A',
            'tax_number' => '123456789012345',
        ]);

        // 2. Short tax number (14 digits) fails
        $response = $this->actingAs($user)->post('/app/purchasing/suppliers', [
            'name' => 'Supplier B',
            'type' => 'parts',
            'contact_person' => 'Jane Doe',
            'phone' => '+966501234567',
            'tax_number' => '12345678901234',
        ]);
        $response->assertSessionHasErrors(['tax_number']);

        // 3. Long tax number (16 digits) fails
        $response = $this->actingAs($user)->post('/app/purchasing/suppliers', [
            'name' => 'Supplier C',
            'type' => 'parts',
            'contact_person' => 'Jane Doe',
            'phone' => '+966501234567',
            'tax_number' => '1234567890123456',
        ]);
        $response->assertSessionHasErrors(['tax_number']);

        // 4. Non-numeric tax number fails
        $response = $this->actingAs($user)->post('/app/purchasing/suppliers', [
            'name' => 'Supplier D',
            'type' => 'parts',
            'contact_person' => 'Jane Doe',
            'phone' => '+966501234567',
            'tax_number' => '12345678901234a',
        ]);
        $response->assertSessionHasErrors(['tax_number']);
    }
}
