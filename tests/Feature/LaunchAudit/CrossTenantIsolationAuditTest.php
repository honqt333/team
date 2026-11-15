<?php

declare(strict_types=1);

namespace Tests\Feature\LaunchAudit;

use App\Models\Center;
use App\Models\Customer;
use App\Models\HR\Employee;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * LAUNCH AUDIT — cross-tenant isolation (tenant A must never reach tenant B).
 *
 * WHY THIS FILE EXISTS
 * --------------------
 * The repository already has `tests/Feature/TenancyIsolationTest.php`, and
 * it fails with "expected 403, received 200". The failure was blamed on a
 * tenant leak. It is not: the test switches the acting user's centre with
 *
 *     $userA->update(['current_center_id' => $centerB->id]);
 *
 * and `User::fill()` (app/Models/User.php:54) blocks `current_center_id`
 * on an EXISTING record, so the update is a SILENT NO-OP. The user never
 * left tenant A, the request was answered from tenant A, and the assertion
 * that was supposed to prove the cross-tenant refusal never happened.
 *
 * The same silent no-op is what makes `WorkOrdersCrudTest`'s "cross branch
 * data sharing" and `InvoiceCenterScopeTest`'s "after switching to it"
 * fail. Three red tests, one cause, and a security contract that has never
 * actually been exercised.
 *
 * These tests therefore:
 *   1. switch the context the way PRODUCTION does (`forceFill`, exactly
 *      as ProfileController and UserController do), and
 *   2. assert the INVARIANT ("tenant A can never see or touch tenant B")
 *      instead of a status code, so a 403-vs-404 difference in a future
 *      refactor does not produce a false alarm.
 */
class CrossTenantIsolationAuditTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenantA;

    private Center $centerA;

    private User $userA;

    private Tenant $tenantB;

    private Center $centerB;

    private Customer $customerB;

    private Vehicle $vehicleB;

    private WorkOrder $workOrderB;

    private Invoice $invoiceB;

    private Employee $employeeB;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ([
            Permissions::CUSTOMERS_VIEW,
            Permissions::CUSTOMERS_CREATE,
            Permissions::CUSTOMERS_UPDATE,
            Permissions::CUSTOMERS_DELETE,
            Permissions::WORK_ORDERS_VIEW,
            Permissions::WORK_ORDERS_CREATE,
            Permissions::WORK_ORDERS_UPDATE,
            Permissions::INVOICES_VIEW,
            Permissions::PURCHASE_PAYMENTS_MANAGE,
            Permissions::HR_EMPLOYEES_VIEW,
        ] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // ── Tenant A ───────────────────────────────────────────────────
        $this->tenantA = Tenant::factory()->create();
        $this->centerA = Center::factory()->create(['tenant_id' => $this->tenantA->id]);

        $this->userA = User::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'current_center_id' => $this->centerA->id,
        ]);
        $this->userA->centers()->attach($this->centerA->id, ['tenant_id' => $this->tenantA->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenantA->id);
        $this->userA->givePermissionTo([
            Permissions::CUSTOMERS_VIEW,
            Permissions::CUSTOMERS_CREATE,
            Permissions::CUSTOMERS_UPDATE,
            Permissions::CUSTOMERS_DELETE,
            Permissions::WORK_ORDERS_VIEW,
            Permissions::WORK_ORDERS_CREATE,
            Permissions::WORK_ORDERS_UPDATE,
            Permissions::INVOICES_VIEW,
            Permissions::PURCHASE_PAYMENTS_MANAGE,
            Permissions::HR_EMPLOYEES_VIEW,
        ]);
        $this->userA->refresh();

        // ── Tenant B ───────────────────────────────────────────────────
        // Built while nobody is authenticated, so the tenancy scopes do
        // not filter the rows out while they are created.
        $this->tenantB = Tenant::factory()->create();
        $this->centerB = Center::factory()->create(['tenant_id' => $this->tenantB->id]);

        $this->customerB = Customer::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'center_id' => $this->centerB->id,
            'name' => 'عميل المستأجر الثاني',
            'phone' => '0555000002',
        ]);

        $this->vehicleB = Vehicle::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'center_id' => $this->centerB->id,
            'customer_id' => $this->customerB->id,
            'plate_number' => 'BBBB22',
        ]);

        $this->workOrderB = WorkOrder::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'center_id' => $this->centerB->id,
            'customer_id' => $this->customerB->id,
            'vehicle_id' => $this->vehicleB->id,
            'code' => 'WO-TENANT-B',
        ]);

        $this->invoiceB = Invoice::create([
            'tenant_id' => $this->tenantB->id,
            'center_id' => $this->centerB->id,
            'customer_id' => $this->customerB->id,
            'invoice_number' => 'INV-TENANT-B',
            'issue_date' => now(),
            'supply_date' => now()->toDateString(),
            'type' => 'invoice',
            'status' => 'valid',
            'payment_status' => 'unpaid',
            'total_excl_tax' => 100,
            'total_tax' => 15,
            'total_incl_tax' => 115,
            'total_paid' => 0,
            'currency_code' => 'SAR',
        ]);

        $this->employeeB = Employee::create([
            'tenant_id' => $this->tenantB->id,
            'center_id' => $this->centerB->id,
            'employee_number' => 'EMP-TB-001',
            'name_ar' => 'موظف المستأجر الثاني',
            'hire_date' => now()->subYear()->toDateString(),
            'base_salary' => 5000,
            'status' => 'active',
        ]);
    }

    /**
     * The anchor. Every other test in this file is meaningless if the
     * acting user's own context is not what the test believes it is.
     */
    public function test_the_acting_user_really_sits_in_tenant_a(): void
    {
        $this->assertSame($this->tenantA->id, $this->userA->tenant_id);
        $this->assertSame($this->centerA->id, $this->userA->current_center_id);

        $this->actingAs($this->userA)
            ->get('/app/work-orders')
            ->assertOk();
    }

    public function test_a_user_cannot_open_a_work_order_of_another_tenant(): void
    {
        $response = $this->actingAs($this->userA)
            ->get('/app/work-orders/'.$this->workOrderB->id);

        $this->assertContains(
            $response->getStatusCode(),
            [403, 404],
            'A work order of another tenant must not be readable.'
        );
        $response->assertDontSee($this->workOrderB->code);
        $response->assertDontSee('WO-TENANT-B');
    }

    public function test_a_user_cannot_open_a_customer_of_another_tenant(): void
    {
        $response = $this->actingAs($this->userA)
            ->get('/app/customers/'.$this->customerB->id);

        $this->assertContains($response->getStatusCode(), [403, 404]);
        $response->assertDontSee('عميل المستأجر الثاني');
    }

    public function test_a_user_cannot_open_an_invoice_of_another_tenant(): void
    {
        $response = $this->actingAs($this->userA)
            ->get('/app/invoices/'.$this->invoiceB->id);

        $this->assertContains($response->getStatusCode(), [403, 404]);
        $response->assertDontSee('INV-TENANT-B');
    }

    public function test_a_user_cannot_open_an_employee_of_another_tenant(): void
    {
        $response = $this->actingAs($this->userA)
            ->get('/app/hr/employees/'.$this->employeeB->id);

        $this->assertContains($response->getStatusCode(), [403, 404]);
        $response->assertDontSee('EMP-TB-001');
    }

    public function test_a_user_cannot_record_a_payment_against_another_tenants_invoice(): void
    {
        $response = $this->actingAs($this->userA)
            ->post('/app/invoices/'.$this->invoiceB->id.'/payments', [
                'amount' => 50,
                'payment_method' => 'cash',
            ]);

        $this->assertContains($response->getStatusCode(), [403, 404]);
        $this->assertDatabaseCount('payments', 0);
        $this->assertSame(
            'unpaid',
            $this->invoiceB->fresh()->payment_status,
            'No payment may be written against a foreign invoice.'
        );
    }

    public function test_a_user_cannot_update_another_tenants_customer(): void
    {
        $response = $this->actingAs($this->userA)
            ->put('/app/customers/'.$this->customerB->id, [
                'name' => 'اسم مُختَرَق',
                'type' => 'individual',
                'phone' => '0555000002',
            ]);

        $this->assertContains($response->getStatusCode(), [403, 404, 422]);

        $fresh = $this->customerB->fresh();
        $this->assertSame('عميل المستأجر الثاني', $fresh->name, 'A foreign customer must not be writable.');
    }

    public function test_a_user_cannot_delete_another_tenants_customer(): void
    {
        $response = $this->actingAs($this->userA)
            ->delete('/app/customers/'.$this->customerB->id);

        $this->assertContains($response->getStatusCode(), [403, 404]);
        $this->assertNotNull(
            $this->customerB->fresh(),
            'A foreign customer must not be deletable.'
        );
    }

    /**
     * The real probe of `EnsureCenterContext`.
     *
     * The context is moved the way production moves it — `forceFill`, as
     * ProfileController (app/Http/Controllers/ProfileController.php:77)
     * and UserController (app/Http/Controllers/App/UserController.php:175)
     * do — so the middleware is asked the question it is meant to answer:
     * "what happens when the session points at a centre of another
     * tenant?".
     */
    public function test_a_user_cannot_move_their_context_into_another_tenant(): void
    {
        $this->userA->forceFill(['current_center_id' => $this->centerB->id])->save();
        $this->userA->refresh();

        $this->assertSame(
            $this->centerB->id,
            $this->userA->current_center_id,
            'The context switch must actually happen for this probe to mean anything.'
        );

        $response = $this->actingAs($this->userA->fresh())
            ->get('/app/work-orders');

        $this->assertSame(
            403,
            $response->getStatusCode(),
            'A session pointing at another tenant\'s centre must be refused.'
        );
        $response->assertDontSee('WO-TENANT-B');
    }

    /**
     * A list endpoint must never contain a foreign row, whatever the
     * status code of the request is.
     */
    public function test_the_work_order_list_never_contains_a_foreign_row(): void
    {
        $response = $this->actingAs($this->userA)
            ->get('/app/work-orders');

        $response->assertOk();
        $response->assertDontSee('WO-TENANT-B');
        $response->assertDontSee('عميل المستأجر الثاني');
    }

    /**
     * The JSON endpoints power the search boxes and pickers. They are
     * easier to abuse than a page because they return raw rows.
     */
    public function test_the_json_endpoints_return_only_the_current_tenants_rows(): void
    {
        $response = $this->actingAs($this->userA)
            ->get('/app/api/customers');

        $response->assertOk();

        $body = $response->getContent();

        $this->assertStringNotContainsString('عميل المستأجر الثاني', $body);
        $this->assertStringNotContainsString('0555000002', $body);
    }

    /**
     * The guard has to hold for the ability itself, not only for the
     * route that happens to use it today.
     */
    public function test_the_policies_refuse_a_foreign_record(): void
    {
        $this->assertFalse(
            Gate::forUser($this->userA)->allows('view', $this->workOrderB),
            'The work order policy must refuse a foreign work order.'
        );
        $this->assertFalse(
            Gate::forUser($this->userA)->allows('view', $this->invoiceB),
            'The invoice policy must refuse a foreign invoice.'
        );
    }
}
