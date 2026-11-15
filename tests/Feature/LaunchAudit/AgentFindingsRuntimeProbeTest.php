<?php

declare(strict_types=1);

namespace Tests\Feature\LaunchAudit;

use App\Models\Center;
use App\Models\Customer;
use App\Models\HR\Allowance;
use App\Models\HR\BiometricDevice;
use App\Models\HR\Employee;
use App\Models\HR\OtherPayment;
use App\Models\Quote;
use App\Models\QuoteLine;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Support\Permissions;
use App\Support\TenantClock;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * LAUNCH AUDIT — runtime proof for the findings of the static sweep.
 *
 * Every test in this file asserts the CORRECT behaviour. A test that
 * FAILS is therefore the proof that the defect is real and reachable —
 * not a flaky test. The fix turns the test green.
 *
 * Each test names the file and the line of the defect it exercises, so
 * the report and the test point at the same piece of code.
 */
class AgentFindingsRuntimeProbeTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Center $center;

    private User $user;

    private Customer $customer;

    private Vehicle $vehicle;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ([
            Permissions::CUSTOMERS_VIEW,
            Permissions::CUSTOMERS_UPDATE,
            Permissions::WORK_ORDERS_VIEW,
            Permissions::WORK_ORDERS_CREATE,
            Permissions::WORK_ORDERS_UPDATE,
            Permissions::HR_EMPLOYEES_VIEW,
            Permissions::HR_EMPLOYEES_UPDATE,
            Permissions::HR_ATTENDANCE_VIEW,
            Permissions::HR_ATTENDANCE_MANAGE,
            Permissions::HR_PAYMENTS_APPROVE,
        ] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $this->tenant = Tenant::factory()->create(['timezone' => 'Asia/Riyadh']);
        $this->center = Center::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
        ]);
        $this->vehicle = Vehicle::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'customer_id' => $this->customer->id,
        ]);

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
        ]);
        $this->user->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
        $this->user->givePermissionTo([
            Permissions::CUSTOMERS_VIEW,
            Permissions::CUSTOMERS_UPDATE,
            Permissions::WORK_ORDERS_VIEW,
            Permissions::WORK_ORDERS_CREATE,
            Permissions::WORK_ORDERS_UPDATE,
            Permissions::HR_EMPLOYEES_VIEW,
            Permissions::HR_EMPLOYEES_UPDATE,
            Permissions::HR_ATTENDANCE_VIEW,
            Permissions::HR_ATTENDANCE_MANAGE,
            Permissions::HR_PAYMENTS_APPROVE,
        ]);
        $this->user->refresh();
    }

    private function workOrder(string $status = WorkOrder::STATUS_OPEN): WorkOrder
    {
        return WorkOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'status' => $status,
        ]);
    }

    /**
     * C1 — app/Http/Requests/WorkOrderUpdateRequest.php:59 accepts `status`,
     * and app/Actions/WorkOrder/UpdateWorkOrderAction.php:21 writes it
     * directly. The transition endpoint
     * (app/Http/Controllers/App/WorkOrders/WorkOrderStatusController.php:185)
     * is the only place that checks `allItemsCompleted()` and issues the
     * invoice.
     *
     * Consequence when it reproduces: the work order is "done", the items
     * are still pending and NO invoice is ever created — the revenue is
     * never billed, never reported to ZATCA and never aged in AR.
     */
    public function test_the_generic_update_may_not_move_a_work_order_to_done(): void
    {
        $workOrder = $this->workOrder();

        $service = Service::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
        ]);

        $workOrder->items()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'service_id' => $service->id,
            'title' => 'خدمة معلقة',
            'qty' => 1,
            'unit_price' => 200,
            'status' => WorkOrderItem::STATUS_PENDING,
        ]);

        $this->actingAs($this->user)
            ->put('/app/work-orders/'.$workOrder->id, [
                'customer_id' => $this->customer->id,
                'vehicle_id' => $this->vehicle->id,
                'status' => WorkOrder::STATUS_DONE,
            ]);

        $this->assertNotSame(
            WorkOrder::STATUS_DONE,
            $workOrder->fresh()->status,
            'The generic update endpoint must not be able to close a work order whose items are still pending.'
        );
    }

    /**
     * H1 — app/Actions/WorkOrder/UpdateWorkOrderAction.php:67 deletes and
     * recreates every item, and the labour entries of an item cascade
     * with it.
     */
    public function test_updating_a_work_order_must_not_destroy_the_labour_entries(): void
    {
        $workOrder = $this->workOrder();

        $service = Service::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
        ]);

        $item = $workOrder->items()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'service_id' => $service->id,
            'title' => 'خدمة مسجّلة',
            'qty' => 1,
            'unit_price' => 200,
            'status' => WorkOrderItem::STATUS_IN_PROGRESS,
        ]);

        $this->assertDatabaseCount('work_order_labor_entries', 0);

        $this->actingAs($this->user)
            ->put('/app/work-orders/'.$workOrder->id, [
                'customer_id' => $this->customer->id,
                'vehicle_id' => $this->vehicle->id,
                'items' => [
                    ['title' => 'خدمة مسجّلة', 'qty' => 1, 'unit_price' => 200],
                ],
            ]);

        $this->assertNotNull(
            $item->fresh(),
            'The item must survive an update — its labour, technician and part rows hang off it.'
        );
    }

    /**
     * H9 — app/Http/Controllers/App/HR/EmployeeFinancialsController.php:22
     * validates `allowances.*.id` with a GLOBAL `exists:` rule, so an
     * allowance of another tenant passes and is written into the pivot.
     */
    public function test_an_employee_cannot_be_given_an_allowance_of_another_tenant(): void
    {
        $tenantB = Tenant::factory()->create();
        $foreignAllowance = Allowance::create([
            'tenant_id' => $tenantB->id,
            'name_ar' => 'علاوة مستأجر آخر',
            'type' => 'fixed',
            'amount' => 5000,
        ]);

        $employee = Employee::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'employee_number' => 'EMP-A-001',
            'name_ar' => 'موظف',
            'hire_date' => now()->subYear()->toDateString(),
            'base_salary' => 5000,
            'status' => 'active',
        ]);

        $this->actingAs($this->user)
            ->put('/app/hr/employees/'.$employee->id.'/allowances', [
                'allowances' => [
                    [
                        'id' => $foreignAllowance->id,
                        'custom_amount' => 5000,
                        'period_type' => 'indefinite',
                    ],
                ],
            ])
            ->assertSessionHasErrors('allowances.0.id');

        $this->assertDatabaseMissing('hr_employee_allowances', [
            'employee_id' => $employee->id,
            'allowance_id' => $foreignAllowance->id,
        ]);
    }

    /**
     * H5 — app/Http/Controllers/App/HR/OtherPaymentsController.php:100-125.
     * `markAsPaid()` checks neither the current status nor a payment
     * permission, and `status` is mass-assignable.
     */
    public function test_a_pending_other_payment_cannot_be_marked_as_paid(): void
    {
        $employee = Employee::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'employee_number' => 'EMP-B-001',
            'name_ar' => 'موظف',
            'hire_date' => now()->subYear()->toDateString(),
            'base_salary' => 5000,
            'status' => 'active',
        ]);

        $payment = OtherPayment::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'employee_id' => $employee->id,
            'type' => 'bonus',
            'title' => 'مكافأة',
            'amount' => 1000,
            'payment_date' => now()->toDateString(),
            'status' => 'pending',
        ]);

        $unprivileged = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
        ]);
        $unprivileged->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);
        $this->actingAs($unprivileged)
            ->put('/app/hr/payroll/other-payments/'.$payment->id.'/pay')
            ->assertStatus(403);

        $response = $this->actingAs($this->user)
            ->put('/app/hr/payroll/other-payments/'.$payment->id.'/pay');

        $this->assertContains(
            $response->getStatusCode(),
            [200, 302],
            'The probe must reach the endpoint — a 403 here means the test never exercised it.'
        );

        $this->assertNotSame(
            'paid',
            $payment->fresh()->status,
            'A payment must not reach "paid" without passing through the approval step.'
        );
    }

    /**
     * H4 — app/Http/Controllers/Api/HR/BiometricAttendanceController.php:56
     * accepts any `timestamp`, so a device token can write attendance for
     * any date in the past or in the future.
     */
    public function test_the_biometric_clock_rejects_a_timestamp_outside_the_window(): void
    {
        $employee = Employee::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'employee_number' => 'EMP-C-001',
            'name_ar' => 'موظف',
            'hire_date' => now()->subYear()->toDateString(),
            'base_salary' => 5000,
            'status' => 'active',
        ]);

        $rawToken = 'device-token-'.$employee->id;

        BiometricDevice::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'name' => 'جهاز',
            'api_token' => $rawToken,
            'token_hash' => BiometricDevice::hashToken($rawToken),
            'is_active' => true,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$rawToken)
            ->postJson('/api/v1/attendance/clock', [
                'employee_identifier' => $employee->employee_number,
                'action' => 'check_in',
                'timestamp' => now()->subDays(40)->toIso8601String(),
            ])->assertStatus(422);

        $this->assertDatabaseMissing('hr_attendances', [
            'employee_id' => $employee->id,
            'date' => now()->subDays(40)->toDateString(),
        ]);
    }

    /**
     * M4 — routes/api.php:43 applies no `tenant.active` to the biometric
     * group, so a suspended tenant's devices keep accruing attendance.
     */
    public function test_the_biometric_api_refuses_a_suspended_tenant(): void
    {
        $employee = Employee::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'employee_number' => 'EMP-D-001',
            'name_ar' => 'موظف',
            'hire_date' => now()->subYear()->toDateString(),
            'base_salary' => 5000,
            'status' => 'active',
        ]);

        $rawToken = 'suspended-tenant-token';

        BiometricDevice::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'name' => 'جهاز',
            'api_token' => $rawToken,
            'token_hash' => BiometricDevice::hashToken($rawToken),
            'is_active' => true,
        ]);

        $this->tenant->forceFill(['status' => 'suspended'])->save();

        $this->withHeader('Authorization', 'Bearer '.$rawToken)
            ->postJson('/api/v1/attendance/clock', [
                'employee_identifier' => $employee->employee_number,
                'action' => 'check_in',
            ])->assertStatus(403);
    }

    /**
     * M5 — app/Http/Controllers/Public/PublicQuoteController.php:143
     * resolves the quote with `whereHas('tenant')`, which proves only that
     * the tenant row exists. A suspended tenant keeps collecting
     * approvals — the one public WRITE path with no lifecycle check.
     */
    public function test_a_suspended_tenant_cannot_collect_a_public_quote_approval(): void
    {
        $quote = Quote::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'code' => 'SUSP-001',
            'status' => Quote::STATUS_SENT,
            'valid_until' => now()->addDays(3),
        ]);

        QuoteLine::create([
            'quote_id' => $quote->id,
            'description' => 'خدمة',
            'qty' => 1,
            'unit_price' => 150,
            'final_unit_price' => 150,
            'line_total' => 150,
        ]);

        $this->tenant->forceFill(['status' => 'suspended'])->save();

        $this->post('/view/quote/'.$quote->uuid.'/approve')
            ->assertSessionHas('error');

        $this->assertSame(
            Quote::STATUS_SENT,
            $quote->fresh()->status,
            'A suspended tenant must not be able to turn a public link into a financial obligation.'
        );
    }

    /**
     * M3 — app/Http/Controllers/App/HR/AttendanceController.php:29 resolves
     * "today" with `Carbon::today()`, which follows
     * `config('app.timezone')` (config/app.php:71 — UTC), while the tenant
     * carries its own timezone that nothing reads.
     *
     * The moment is chosen so that the UTC day and the Riyadh day differ:
     * 23:30 UTC is already 02:30 of the next day in Riyadh.
     */
    public function test_today_must_follow_the_tenant_timezone(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-14 23:30:00', 'UTC'));

        $appToday = TenantClock::todayDateString($this->tenant);
        $tenantToday = Carbon::now($this->tenant->timezone)->toDateString();

        $this->assertSame(
            $tenantToday,
            $appToday,
            'The application must resolve "today" in the tenant\'s timezone. '
            .'The tenant is '.$this->tenant->timezone.' (today '.$tenantToday.') '
            .'while the application says '.$appToday.'.'
        );

        Carbon::setTestNow();
    }
}
