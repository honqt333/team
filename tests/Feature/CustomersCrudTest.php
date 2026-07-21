<?php

declare(strict_types=1);

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

    public function test_authorized_user_can_create_and_list_customers(): void
    {
        $user = $this->createUserWithPermissions([
            'crm.customers.view',
            'crm.customers.create',
        ]);

        // Create customer with valid Saudi phone number (966 + 9 digits)
        $response = $this->actingAs($user)->postJson('/app/customers', [
            'type' => 'individual',
            'name' => 'John Doe',
            'phone' => '+966501234567',
            'email' => 'john@example.com',
        ]);

        // Web controller returns redirect on successful creation
        $response->assertRedirect();

        // Verify customer was created
        $this->assertDatabaseHas('customers', [
            'name' => 'John Doe',
            'phone' => '+966501234567',
        ]);

        // List customers via API
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
            'phone' => '+966509876543',
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

    public function test_validation_tax_number_must_be_15_digits(): void
    {
        $user = $this->createUserWithPermissions(['crm.customers.create']);

        // Test with invalid letters
        $response = $this->actingAs($user)->postJson('/app/customers', [
            'type' => 'individual',
            'name' => 'John Doe',
            'phone' => '+966501234567',
            'tax_number' => '1234567890abcde',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['tax_number']);

        // Test with less than 15 digits
        $response = $this->actingAs($user)->postJson('/app/customers', [
            'type' => 'individual',
            'name' => 'John Doe',
            'phone' => '+966501234567',
            'tax_number' => '1234567890',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['tax_number']);

        // Test with more than 15 digits
        $response = $this->actingAs($user)->postJson('/app/customers', [
            'type' => 'individual',
            'name' => 'John Doe',
            'phone' => '+966501234567',
            'tax_number' => '1234567890123456',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['tax_number']);

        // Test with exactly 15 digits
        $response = $this->actingAs($user)->postJson('/app/customers', [
            'type' => 'individual',
            'name' => 'John Doe',
            'phone' => '+966501234567',
            'tax_number' => '123456789012345',
        ]);
        $response->assertRedirect();
    }

    public function test_check_phone_endpoint_detects_duplicates(): void
    {
        $user = $this->createUserWithPermissions(['crm.customers.view']);

        // Create a customer with standard phone number format
        $customer = Customer::create([
            'tenant_id' => $user->tenant_id,
            'center_id' => $user->current_center_id,
            'type' => 'individual',
            'name' => 'Existing Customer',
            'phone' => '+966501234567',
        ]);

        // 1. Check a phone number that doesn't exist
        $response1 = $this->actingAs($user)->getJson('/app/customers/check-phone?phone=0509999999');
        $response1->assertOk();
        $response1->assertJson(['exists' => false]);

        // 2. Check the existing phone number (in standard format)
        $response2 = $this->actingAs($user)->getJson('/app/customers/check-phone?phone=+966501234567');
        $response2->assertOk();
        $response2->assertJson([
            'exists' => true,
            'customer' => [
                'id' => $customer->id,
                'name' => 'Existing Customer',
                'phone' => '+966501234567',
            ],
        ]);

        // 3. Check the existing phone number with local formatting (unnormalized 0501234567)
        $response3 = $this->actingAs($user)->getJson('/app/customers/check-phone?phone=0501234567');
        $response3->assertOk();
        $response3->assertJson([
            'exists' => true,
            'customer' => [
                'id' => $customer->id,
                'name' => 'Existing Customer',
            ],
        ]);
    }
}
