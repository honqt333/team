<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CustomersCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Reset permission cache
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create all required permissions for guard 'web'
        Permission::findOrCreate('crm.customers.view', 'web');
        Permission::findOrCreate('crm.customers.create', 'web');
        Permission::findOrCreate('crm.customers.update', 'web');
        Permission::findOrCreate('crm.customers.delete', 'web');
    }

    protected function createUserWithPermissions(array $permissions): User
    {
        $tenant = Tenant::factory()->create();
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
        ]);

        $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);

        // Assign permissions to user
        foreach ($permissions as $permissionName) {
            $user->givePermissionTo($permissionName);
        }

        // Refresh user to load permissions
        $user->refresh();

        return $user;
    }

    public function test_authorized_user_can_create_and_list_customers(): void
    {
        $user = $this->createUserWithPermissions([
            'crm.customers.view',
            'crm.customers.create',
        ]);

        // Create customer
        $response = $this->actingAs($user)->postJson('/app/customers', [
            'type' => 'individual',
            'name' => 'John Doe',
            'phone' => '1234567890',
            'email' => 'john@example.com',
        ]);

        $response->assertStatus(201);
        $response->assertJsonFragment(['name' => 'John Doe']);

        // List customers
        $listResponse = $this->actingAs($user)->getJson('/app/api/customers');
        $listResponse->assertStatus(200);
        $listResponse->assertJsonFragment(['name' => 'John Doe']);
    }

    public function test_user_without_permission_cannot_create(): void
    {
        // User with NO permissions
        $user = $this->createUserWithPermissions([]);

        $response = $this->actingAs($user)->postJson('/app/customers', [
            'type' => 'individual',
            'name' => 'Jane Doe',
            'phone' => '0987654321',
        ]);

        $response->assertStatus(403);
    }

    public function test_user_cannot_access_customer_from_another_center(): void
    {
        $user = $this->createUserWithPermissions(['crm.customers.view']);

        // Create another tenant/center with a customer
        $otherTenant = Tenant::factory()->create();
        $otherCenter = Center::factory()->create(['tenant_id' => $otherTenant->id]);
        $otherCustomer = Customer::factory()->create([
            'tenant_id' => $otherTenant->id,
            'center_id' => $otherCenter->id,
        ]);

        // Try to access customer from another center
        $response = $this->actingAs($user)->getJson("/app/customers/{$otherCustomer->id}");

        $response->assertStatus(404);
    }

    public function test_validation_company_requires_contact_name(): void
    {
        $user = $this->createUserWithPermissions(['crm.customers.create']);

        $response = $this->actingAs($user)->postJson('/app/customers', [
            'type' => 'company',
            'name' => 'Acme Corp',
            'phone' => '1234567890',
            // Missing contact_name
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['contact_name']);
    }
}
