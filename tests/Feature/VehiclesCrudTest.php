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

class VehiclesCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Reset permission cache
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create all required permissions for guard 'web'
        Permission::findOrCreate('crm.vehicles.view', 'web');
        Permission::findOrCreate('crm.vehicles.create', 'web');
        Permission::findOrCreate('crm.vehicles.update', 'web');
        Permission::findOrCreate('crm.vehicles.delete', 'web');
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

    public function test_authorized_user_can_create_vehicle_with_valid_vin(): void
    {
        $user = $this->createUserWithPermissions([
            'crm.vehicles.view',
            'crm.vehicles.create',
        ]);

        $customer = Customer::factory()->create([
            'tenant_id' => $user->tenant_id,
            'center_id' => $user->current_center_id,
        ]);

        $response = $this->actingAs($user)->postJson('/app/vehicles', [
            'customer_id' => $customer->id,
            'plate_number' => 'ABC 1234',
            'vin' => '1HGCR2F83HA123456', // Valid English alphanumeric VIN
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('vehicles', [
            'customer_id' => $customer->id,
            'plate_number' => 'ABC 1234',
            'vin' => '1HGCR2F83HA123456',
        ]);
    }

    public function test_validation_vin_must_be_english_alphanumeric(): void
    {
        $user = $this->createUserWithPermissions(['crm.vehicles.create']);

        $customer = Customer::factory()->create([
            'tenant_id' => $user->tenant_id,
            'center_id' => $user->current_center_id,
        ]);

        // Test with Arabic digits in VIN: should be converted to English and PASS
        $response = $this->actingAs($user)->postJson('/app/vehicles', [
            'customer_id' => $customer->id,
            'plate_number' => 'ABC 1234',
            'vin' => '1HGCR2F83HA١٢٣٤٥٦', // Contains Arabic numerals, will be converted to 1HGCR2F83HA123456
        ]);
        $response->assertRedirect(); // Successfully redirected (created)

        // Test with Arabic letters in VIN: should FAIL
        $response = $this->actingAs($user)->postJson('/app/vehicles', [
            'customer_id' => $customer->id,
            'plate_number' => 'ABC 5678',
            'vin' => '1HGCR2F83HAأبج', // Contains Arabic letters
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['vin']);

        // Test with special characters in VIN: should FAIL
        $response = $this->actingAs($user)->postJson('/app/vehicles', [
            'customer_id' => $customer->id,
            'plate_number' => 'ABC 9999',
            'vin' => '1HGCR-2F83-HA123', // Contains dashes
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['vin']);

        // Test with space in VIN: should FAIL
        $response = $this->actingAs($user)->postJson('/app/vehicles', [
            'customer_id' => $customer->id,
            'plate_number' => 'ABC 8888',
            'vin' => '1HGCR 2F83 HA123', // Contains space
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['vin']);

        // Test with valid alphanumeric lower/uppercase VIN: should PASS
        $response = $this->actingAs($user)->postJson('/app/vehicles', [
            'customer_id' => $customer->id,
            'plate_number' => 'XYZ 9876',
            'vin' => '1hgcr2f83ha123456', // Valid English letters & numbers
        ]);
        $response->assertRedirect();
    }
}
