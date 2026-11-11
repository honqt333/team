<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Customer;
use App\Models\Quote;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Branch isolation for work orders and quotes, exercised through the
 * real HTTP routes.
 *
 * Approved rule: a record is accessible only from the user's current
 * center (current_center_id). A user assigned to several branches
 * switches the current center; EnsureCenterContext verifies the pivot
 * before the request reaches the policy.
 */
class WorkOrderQuoteBranchIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Tenant $otherTenant;

    private Center $centerA;

    private Center $centerB;

    private Center $otherCenter;

    private User $singleBranchUser;

    private User $multiBranchUser;

    private User $otherTenantUser;

    private Customer $customerA;

    private Customer $customerB;

    private Vehicle $vehicleA;

    private Vehicle $vehicleB;

    private WorkOrder $workOrderA;

    private WorkOrder $workOrderB;

    private Quote $quoteA;

    private Quote $quoteB;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ([
            Permissions::WORK_ORDERS_VIEW,
            Permissions::WORK_ORDERS_UPDATE,
            Permissions::QUOTES_VIEW,
            Permissions::QUOTES_UPDATE,
        ] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $this->tenant = Tenant::factory()->create(['status' => 'active']);
        $this->otherTenant = Tenant::factory()->create(['status' => 'active']);

        $this->centerA = Center::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->centerB = Center::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->otherCenter = Center::factory()->create(['tenant_id' => $this->otherTenant->id]);

        $this->singleBranchUser = $this->makeUser($this->tenant, [$this->centerA], $this->centerA);
        $this->multiBranchUser = $this->makeUser($this->tenant, [$this->centerA, $this->centerB], $this->centerB);
        $this->otherTenantUser = $this->makeUser($this->otherTenant, [$this->otherCenter], $this->otherCenter);

        [$this->customerA, $this->vehicleA] = $this->makeCustomerVehicle($this->tenant, $this->centerA);
        [$this->customerB, $this->vehicleB] = $this->makeCustomerVehicle($this->tenant, $this->centerB);

        $this->workOrderA = $this->makeWorkOrder($this->tenant, $this->centerA, $this->customerA, $this->vehicleA, 'WO-BRANCH-A');
        $this->workOrderB = $this->makeWorkOrder($this->tenant, $this->centerB, $this->customerB, $this->vehicleB, 'WO-BRANCH-B');

        $this->quoteA = $this->makeQuote($this->tenant, $this->centerA, $this->customerA, $this->vehicleA, 'QT-BRANCH-A');
        $this->quoteB = $this->makeQuote($this->tenant, $this->centerB, $this->customerB, $this->vehicleB, 'QT-BRANCH-B');
    }

    public function test_single_branch_user_can_open_and_update_own_branch_records(): void
    {
        $this->actingAsWithTeam($this->singleBranchUser);

        $this->get(route('work-orders.show', $this->workOrderA))->assertOk();
        $this->put(route('work-orders.update', $this->workOrderA), [
            'notes' => 'updated in own branch',
        ])->assertRedirect();

        $this->assertSame('updated in own branch', $this->workOrderA->fresh()->notes);

        $this->get(route('app.quotes.show', $this->quoteA))->assertOk();
        $this->put(route('app.quotes.update', $this->quoteA), [
            'customer_id' => $this->customerA->id,
            'vehicle_id' => $this->vehicleA->id,
            'notes' => 'quote updated in own branch',
        ])->assertRedirect();

        $this->assertSame('quote updated in own branch', $this->quoteA->fresh()->notes);
    }

    public function test_single_branch_user_cannot_open_or_update_a_sibling_branch(): void
    {
        $this->actingAsWithTeam($this->singleBranchUser);

        $this->get(route('work-orders.show', $this->workOrderB))->assertForbidden();
        $this->put(route('work-orders.update', $this->workOrderB), [
            'notes' => 'should never be written',
        ])->assertForbidden();

        // Quotes are hidden by the center global scope during route
        // binding, so the sibling branch answers 404. Either status is a
        // rejection; what matters is that the record stays untouched.
        $this->get(route('app.quotes.show', $this->quoteB))->assertNotFound();
        $this->put(route('app.quotes.update', $this->quoteB), [
            'customer_id' => $this->customerB->id,
            'vehicle_id' => $this->vehicleB->id,
            'notes' => 'should never be written',
        ])->assertNotFound();

        $this->assertNull($this->workOrderB->fresh()->notes);
        $this->assertNull($this->quoteB->fresh()->notes);
    }

    public function test_multi_branch_user_accesses_each_branch_by_switching_current_center(): void
    {
        $this->actingAsWithTeam($this->multiBranchUser);

        // Current center is B, so branch B is reachable and branch A is not.
        $this->get(route('work-orders.show', $this->workOrderB))->assertOk();
        $this->put(route('work-orders.update', $this->workOrderB), [
            'notes' => 'branch B update',
        ])->assertRedirect();
        $this->get(route('work-orders.show', $this->workOrderA))->assertForbidden();

        // Switch the active branch. The pivot already allows center A.
        $this->multiBranchUser->current_center_id = $this->centerA->id;
        $this->multiBranchUser->save();
        $this->actingAsWithTeam($this->multiBranchUser->fresh());

        $this->get(route('work-orders.show', $this->workOrderA))->assertOk();
        $this->put(route('work-orders.update', $this->workOrderA), [
            'notes' => 'branch A update',
        ])->assertRedirect();

        $this->get(route('app.quotes.show', $this->quoteA))->assertOk();
        $this->put(route('app.quotes.update', $this->quoteA), [
            'customer_id' => $this->customerA->id,
            'vehicle_id' => $this->vehicleA->id,
            'notes' => 'quote branch A update',
        ])->assertRedirect();

        $this->assertSame('branch B update', $this->workOrderB->fresh()->notes);
        $this->assertSame('branch A update', $this->workOrderA->fresh()->notes);
        $this->assertSame('quote branch A update', $this->quoteA->fresh()->notes);
    }

    public function test_other_tenant_user_cannot_open_or_update_the_records(): void
    {
        $this->actingAsWithTeam($this->otherTenantUser);

        $this->get(route('work-orders.show', $this->workOrderA))->assertNotFound();
        $this->put(route('work-orders.update', $this->workOrderA), [
            'notes' => 'cross tenant write',
        ])->assertNotFound();

        $this->get(route('app.quotes.show', $this->quoteA))->assertNotFound();
        $this->put(route('app.quotes.update', $this->quoteA), [
            'customer_id' => $this->customerA->id,
            'vehicle_id' => $this->vehicleA->id,
            'notes' => 'cross tenant write',
        ])->assertNotFound();

        $this->assertNull($this->workOrderA->fresh()->notes);
        $this->assertNull($this->quoteA->fresh()->notes);
    }

    /**
     * @param array<int, Center> $centers
     */
    private function makeUser(Tenant $tenant, array $centers, Center $currentCenter): User
    {
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $currentCenter->id,
        ]);

        foreach ($centers as $center) {
            $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        $user->givePermissionTo([
            Permissions::WORK_ORDERS_VIEW,
            Permissions::WORK_ORDERS_UPDATE,
            Permissions::QUOTES_VIEW,
            Permissions::QUOTES_UPDATE,
        ]);

        return $user;
    }

    /**
     * @return array{0: Customer, 1: Vehicle}
     */
    private function makeCustomerVehicle(Tenant $tenant, Center $center): array
    {
        $customer = Customer::factory()->create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
        ]);

        $vehicle = Vehicle::factory()->create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
        ]);

        return [$customer, $vehicle];
    }

    private function makeWorkOrder(
        Tenant $tenant,
        Center $center,
        Customer $customer,
        Vehicle $vehicle,
        string $code,
    ): WorkOrder {
        return WorkOrder::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'code' => $code,
            'status' => WorkOrder::STATUS_OPEN,
            'currency_code' => 'SAR',
            'tax_enabled_snapshot' => true,
            'pricing_mode_snapshot' => 'exclusive',
            'tax_rate_snapshot' => 15,
        ]);
    }

    private function makeQuote(
        Tenant $tenant,
        Center $center,
        Customer $customer,
        Vehicle $vehicle,
        string $code,
    ): Quote {
        return Quote::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'code' => $code,
            'status' => Quote::STATUS_DRAFT,
        ]);
    }
}
