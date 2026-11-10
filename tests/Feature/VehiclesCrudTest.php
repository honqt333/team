<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Quote;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
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

    public function test_vehicle_show_and_customer_balance_api_include_customer_balance_and_credit_limit(): void
    {
        $user = $this->createUserWithPermissions([
            'crm.vehicles.view',
        ]);

        $customer = Customer::create([
            'tenant_id' => $user->tenant_id,
            'center_id' => $user->current_center_id,
            'type' => 'company',
            'name' => 'Al-Amal Transport',
            'phone' => '+966500000001',
            'credit_limit' => 2000.00,
        ]);

        $vehicle = Vehicle::create([
            'tenant_id' => $user->tenant_id,
            'center_id' => $user->current_center_id,
            'customer_id' => $customer->id,
            'plate_number' => 'AAA 1111',
            'vin' => '1HGCR2F83HA111111',
        ]);

        Invoice::create([
            'tenant_id' => $user->tenant_id,
            'center_id' => $user->current_center_id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-VEH-001',
            'issue_date' => now(),
            'supply_date' => now(),
            'status' => 'valid',
            'total_incl_tax' => 750.00,
            'total_paid' => 0.00,
        ]);

        // 1. Check vehicle show endpoint loads customer balance
        $response = $this->actingAs($user)->get("/app/vehicles/{$vehicle->id}");
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Vehicles/Show')
            ->has('vehicle')
            ->where('customer.balance', fn ($v) => (float) $v === 750.0)
            ->where('customer.credit_limit', fn ($v) => (float) $v === 2000.0)
            ->where('customer.is_credit_exceeded', false)
            ->where('customer.available_credit', fn ($v) => (float) $v === 1250.0)
        );

        // 2. Check customer balance API
        $apiResponse = $this->actingAs($user)->getJson("/app/api/customers/{$customer->id}/balance");
        $apiResponse->assertOk();
        $apiResponse->assertJson([
            'customer_id' => $customer->id,
            'balance' => 750.0,
            'credit_limit' => 2000.0,
            'is_credit_exceeded' => false,
            'available_credit' => 1250.0,
        ]);
    }

    public function test_authorized_user_can_merge_vehicles(): void
    {
        $user = $this->createUserWithPermissions([
            'crm.vehicles.view',
            'crm.vehicles.update',
        ]);

        $customer = Customer::create([
            'tenant_id' => $user->tenant_id,
            'center_id' => $user->current_center_id,
            'type' => 'individual',
            'name' => 'Fahad Al-Harbi',
            'phone' => '+966555555555',
        ]);

        $sourceVehicle = Vehicle::create([
            'tenant_id' => $user->tenant_id,
            'center_id' => $user->current_center_id,
            'customer_id' => $customer->id,
            'plate_number' => 'OLD 1111',
            'make_other' => 'Toyota',
            'vin' => 'SOURCEVIN12345678',
            'odometer' => 50000,
        ]);

        $targetVehicle = Vehicle::create([
            'tenant_id' => $user->tenant_id,
            'center_id' => $user->current_center_id,
            'customer_id' => $customer->id,
            'plate_number' => 'NEW 2222',
            'make_other' => 'Toyota',
            'vin' => 'TARGETVIN12345678',
            'odometer' => 45000,
        ]);

        // Create a work order for source vehicle
        $workOrder = WorkOrder::create([
            'tenant_id' => $user->tenant_id,
            'center_id' => $user->current_center_id,
            'customer_id' => $customer->id,
            'vehicle_id' => $sourceVehicle->id,
            'code' => 'WO-MERGE-01',
            'status' => 'open',
        ]);

        // Create a quote for source vehicle
        $quote = Quote::create([
            'tenant_id' => $user->tenant_id,
            'center_id' => $user->current_center_id,
            'customer_id' => $customer->id,
            'vehicle_id' => $sourceVehicle->id,
            'code' => 'QT-000001',
            'status' => 'draft',
            'total_excl_tax' => 100,
            'total_tax' => 15,
            'total_incl_tax' => 115,
        ]);

        // 1. Test mergeData endpoint
        $mergeDataResponse = $this->actingAs($user)->getJson("/app/vehicles/{$sourceVehicle->id}/merge");
        $mergeDataResponse->assertOk();
        $mergeDataResponse->assertJsonStructure([
            'source' => ['id', 'plate_number', 'work_orders_count', 'quotes_count'],
            'targets' => [
                '*' => ['id', 'plate_number', 'is_same_customer', 'is_same_make'],
            ],
        ]);

        // 2. Test cannot merge vehicle with itself
        $selfMergeResponse = $this->actingAs($user)->post("/app/vehicles/{$sourceVehicle->id}/merge/{$sourceVehicle->id}");
        $selfMergeResponse->assertSessionHas('error');

        // 3. Test cannot merge vehicles of DIFFERENT customers
        $otherCustomer = Customer::create([
            'tenant_id' => $user->tenant_id,
            'center_id' => $user->current_center_id,
            'type' => 'individual',
            'name' => 'Other Customer',
            'phone' => '+966599999999',
        ]);
        $diffCustomerVehicle = Vehicle::create([
            'tenant_id' => $user->tenant_id,
            'center_id' => $user->current_center_id,
            'customer_id' => $otherCustomer->id,
            'plate_number' => 'DIFF 3333',
            'make_other' => 'Toyota',
        ]);
        $diffCustomerMergeResponse = $this->actingAs($user)->post("/app/vehicles/{$sourceVehicle->id}/merge/{$diffCustomerVehicle->id}");
        $diffCustomerMergeResponse->assertSessionHas('error');

        // 4. Test cannot merge vehicles of DIFFERENT makes (even if same customer)
        $diffMakeVehicle = Vehicle::create([
            'tenant_id' => $user->tenant_id,
            'center_id' => $user->current_center_id,
            'customer_id' => $customer->id,
            'plate_number' => 'DIFFMAKE 4444',
            'make_other' => 'Hyundai',
        ]);
        $diffMakeMergeResponse = $this->actingAs($user)->post("/app/vehicles/{$sourceVehicle->id}/merge/{$diffMakeVehicle->id}");
        $diffMakeMergeResponse->assertSessionHas('error');

        // 5. Test successful merge between same-customer & same-make vehicles
        $response = $this->actingAs($user)->post("/app/vehicles/{$sourceVehicle->id}/merge/{$targetVehicle->id}");
        $response->assertRedirect(route('vehicles.show', $targetVehicle));

        // Assert work order and quote were moved to target vehicle
        $this->assertEquals($targetVehicle->id, $workOrder->fresh()->vehicle_id);
        $this->assertEquals($targetVehicle->id, $quote->fresh()->vehicle_id);

        // Assert odometer was updated to higher reading (50000)
        $this->assertEquals(50000, $targetVehicle->fresh()->odometer);

        // Assert source vehicle was soft-deleted
        $this->assertSoftDeleted('vehicles', ['id' => $sourceVehicle->id]);
    }

    public function test_vehicles_index_sorts_by_column_and_direction(): void
    {
        $user = $this->createUserWithPermissions([
            'crm.vehicles.view',
        ]);

        $customerA = Customer::factory()->create([
            'tenant_id' => $user->tenant_id,
            'name' => 'Alpha Customer',
        ]);

        $customerZ = Customer::factory()->create([
            'tenant_id' => $user->tenant_id,
            'name' => 'Zulu Customer',
        ]);

        $vehicle1 = Vehicle::create([
            'tenant_id' => $user->tenant_id,
            'center_id' => $user->current_center_id,
            'customer_id' => $customerA->id,
            'plate_number' => 'AAA 1111',
            'year' => 2010,
        ]);

        $vehicle2 = Vehicle::create([
            'tenant_id' => $user->tenant_id,
            'center_id' => $user->current_center_id,
            'customer_id' => $customerZ->id,
            'plate_number' => 'ZZZ 9999',
            'year' => 2024,
        ]);

        // 1. Sort by plate_number asc
        $responseAsc = $this->actingAs($user)->get(route('vehicles.index', [
            'sort' => 'plate_number',
            'direction' => 'asc',
        ]));
        $responseAsc->assertOk();
        $vehiclesAsc = $responseAsc->inertiaPage()['props']['vehicles']['data'];
        $this->assertEquals('AAA 1111', $vehiclesAsc[0]['plate_number']);
        $this->assertEquals('ZZZ 9999', $vehiclesAsc[1]['plate_number']);

        // 2. Sort by plate_number desc
        $responseDesc = $this->actingAs($user)->get(route('vehicles.index', [
            'sort' => 'plate_number',
            'direction' => 'desc',
        ]));
        $responseDesc->assertOk();
        $vehiclesDesc = $responseDesc->inertiaPage()['props']['vehicles']['data'];
        $this->assertEquals('ZZZ 9999', $vehiclesDesc[0]['plate_number']);
        $this->assertEquals('AAA 1111', $vehiclesDesc[1]['plate_number']);

        // 3. Sort by customer asc (via subquery)
        $responseCustAsc = $this->actingAs($user)->get(route('vehicles.index', [
            'sort' => 'customer',
            'direction' => 'asc',
        ]));
        $responseCustAsc->assertOk();
        $vehiclesCustAsc = $responseCustAsc->inertiaPage()['props']['vehicles']['data'];
        $this->assertEquals($customerA->id, $vehiclesCustAsc[0]['customer_id']);
        $this->assertEquals($customerZ->id, $vehiclesCustAsc[1]['customer_id']);

        // 4. Sort by year desc
        $responseYearDesc = $this->actingAs($user)->get(route('vehicles.index', [
            'sort' => 'year',
            'direction' => 'desc',
        ]));
        $responseYearDesc->assertOk();
        $vehiclesYearDesc = $responseYearDesc->inertiaPage()['props']['vehicles']['data'];
        $this->assertEquals(2024, $vehiclesYearDesc[0]['year']);
        $this->assertEquals(2010, $vehiclesYearDesc[1]['year']);
    }
}
