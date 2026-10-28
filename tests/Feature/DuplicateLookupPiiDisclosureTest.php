<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Tests for the PII disclosure through the duplicate-lookup endpoints
 * (audit finding #15).
 *
 * THE BUG
 *
 * Two JSON endpoints answered "does this phone / plate already exist?"
 * without any authorization check:
 *
 *   GET /app/customers/check-phone  -> CustomerController::checkPhone()
 *   GET /app/vehicles/check-plate   -> VehicleController::checkPlate()
 *
 * Both returned far more than a boolean. checkPhone() answered with the
 * customer's name, phone, email, type, tax number and address.
 * checkPlate() answered with the vehicle's plate, VIN, year and colour
 * plus the owning customer's name and phone.
 *
 * Any authenticated user in the tenant could therefore call the
 * endpoints with a phone number or a plate and harvest the customer
 * roster, even without `crm.customers.view` / `crm.vehicles.view` —
 * the two permissions that gate the very pages those details link to.
 *
 * THE FIX
 *
 *   - The controllers call authorize('viewAny', ...) so the endpoints
 *     need the same read permission as the pages they feed.
 *   - The routes carry a `record-lookup` throttle so an account that
 *     does hold the permission cannot sweep the tenant's records one
 *     number at a time.
 */
class DuplicateLookupPiiDisclosureTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Center $center;

    protected function setUp(): void
    {
        parent::setUp();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach ([
            Permissions::CUSTOMERS_VIEW,
            Permissions::CUSTOMERS_CREATE,
            Permissions::VEHICLES_VIEW,
            Permissions::VEHICLES_CREATE,
        ] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $this->tenant = Tenant::factory()->create();
        $this->center = Center::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    private function createUser(array $permissions = []): User
    {
        $user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
        ]);

        $user->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);

        foreach ($permissions as $permission) {
            $user->givePermissionTo($permission);
        }

        $user->refresh();

        return $user;
    }

    private function seedCustomer(): Customer
    {
        return Customer::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'type' => 'individual',
            'name' => 'Private Person',
            'phone' => '+966500123456',
            'email' => 'private.person@example.com',
            'tax_number' => '300000000000003',
            'address_line' => '123 Secret Street',
        ]);
    }

    private function seedVehicle(Customer $customer): Vehicle
    {
        return Vehicle::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'customer_id' => $customer->id,
            'plate_number' => 'ABC 1234',
            'vin' => 'VIN-SECRET-0001',
            'year' => 2020,
        ]);
    }

    /**
     * A user with no customer read permission must not be able to
     * harvest the customer roster by phone number.
     */
    public function test_check_phone_is_forbidden_without_the_customers_read_permission(): void
    {
        $customer = $this->seedCustomer();
        $user = $this->createUser([Permissions::CUSTOMERS_CREATE]);

        $response = $this->actingAs($user)
            ->getJson('/app/customers/check-phone?phone='.urlencode($customer->phone));

        $response->assertForbidden();

        $this->assertStringNotContainsString(
            'private.person@example.com',
            $response->getContent(),
            'The customer email must not be disclosed without the read permission.'
        );
        $this->assertStringNotContainsString(
            '300000000000003',
            $response->getContent(),
            'The customer tax number must not be disclosed without the read permission.'
        );
    }

    /**
     * The same for the plate lookup, which leaks the VIN and the
     * owning customer's contact details.
     */
    public function test_check_plate_is_forbidden_without_the_vehicles_read_permission(): void
    {
        $customer = $this->seedCustomer();
        $vehicle = $this->seedVehicle($customer);
        $user = $this->createUser([Permissions::VEHICLES_CREATE]);

        $response = $this->actingAs($user)
            ->getJson('/app/vehicles/check-plate?plate_number='.urlencode($vehicle->plate_number));

        $response->assertForbidden();

        $this->assertStringNotContainsString(
            'VIN-SECRET-0001',
            $response->getContent(),
            'The vehicle VIN must not be disclosed without the read permission.'
        );
        $this->assertStringNotContainsString(
            'Private Person',
            $response->getContent(),
            'The customer name must not be disclosed without the read permission.'
        );
    }

    /**
     * A user with no permissions at all is refused as well.
     */
    public function test_check_phone_is_forbidden_for_a_user_without_any_permission(): void
    {
        $customer = $this->seedCustomer();
        $user = $this->createUser();

        $this->actingAs($user)
            ->getJson('/app/customers/check-phone?phone='.urlencode($customer->phone))
            ->assertForbidden();
    }

    /**
     * The legitimate flow must keep working — the duplicate-check UI
     * depends on it and shows the matched record.
     */
    public function test_check_phone_still_works_with_the_customers_read_permission(): void
    {
        $customer = $this->seedCustomer();
        $user = $this->createUser([Permissions::CUSTOMERS_VIEW]);

        $response = $this->actingAs($user)
            ->getJson('/app/customers/check-phone?phone='.urlencode($customer->phone));

        $response->assertOk();

        $response->assertJsonPath('exists', true);
        $response->assertJsonPath('customer.id', $customer->id);
        $response->assertJsonPath('customer.name', 'Private Person');
    }

    public function test_check_plate_still_works_with_the_vehicles_read_permission(): void
    {
        $customer = $this->seedCustomer();
        $vehicle = $this->seedVehicle($customer);
        $user = $this->createUser([Permissions::VEHICLES_VIEW]);

        $response = $this->actingAs($user)
            ->getJson('/app/vehicles/check-plate?plate_number='.urlencode($vehicle->plate_number));

        $response->assertOk();

        $response->assertJsonPath('exists', true);
        $response->assertJsonPath('vehicle.id', $vehicle->id);
    }

    /**
     * A miss still answers "not found" rather than a forbidden error —
     * the check is about permission, not about whether the record
     * exists.
     */
    public function test_an_unknown_phone_still_answers_not_found_for_a_permitted_user(): void
    {
        $user = $this->createUser([Permissions::CUSTOMERS_VIEW]);

        $this->actingAs($user)
            ->getJson('/app/customers/check-phone?phone=%2B966500999999')
            ->assertOk()
            ->assertJsonPath('exists', false);
    }
}
