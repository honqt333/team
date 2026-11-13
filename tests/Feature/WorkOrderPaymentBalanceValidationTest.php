<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PaymentType;
use App\Models\Center;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class WorkOrderPaymentBalanceValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach ([
            'crm.work_orders.view',
            'crm.work_orders.update',
            'crm.payments.create',
            'crm.payments.update',
        ] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
    }

    protected function createTenantContext(): array
    {
        $tenant = Tenant::factory()->create();
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
        ]);
        $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        $user->givePermissionTo([
            'crm.work_orders.view',
            'crm.work_orders.update',
            'crm.payments.create',
            'crm.payments.update',
        ]);

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'type' => 'individual',
            'name' => 'Test Customer',
            'phone' => '050'.random_int(1000000, 9999999),
        ]);

        $vehicle = Vehicle::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'plate_number' => strtoupper(Str::random(3)).' '.random_int(1000, 9999),
        ]);

        $user->refresh();

        return [$tenant, $center, $user, $customer, $vehicle];
    }

    public function test_payment_exceeding_balance_on_open_work_order_is_rejected(): void
    {
        [$tenant, $center, $user, $customer, $vehicle] = $this->createTenantContext();

        $workOrder = WorkOrder::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'code' => 'WO-PAY-001',
            'status' => WorkOrder::STATUS_OPEN,
        ]);

        WorkOrderItem::create([
            'work_order_id' => $workOrder->id,
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'title' => 'Brake Service',
            'qty' => 1,
            'unit_price' => 300,
            'min_price_snapshot' => 0.00,
            'status' => WorkOrderItem::STATUS_COMPLETED,
        ]);

        // Attempt payment greater than balance (300)
        $this->actingAs($user)
            ->post(route('work-orders.payments.store', $workOrder->id), [
                'type' => 'payment',
                'amount' => 350,
                'payment_method' => 'cash',
                'payment_date' => now()->toDateString(),
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('amount')
            ->assertSessionHas('error', __('payments.amount_exceeds_balance'));

        $this->assertDatabaseCount('payments', 0);

        // Valid partial payment succeeds
        $this->actingAs($user)
            ->post(route('work-orders.payments.store', $workOrder->id), [
                'type' => 'payment',
                'amount' => 200,
                'payment_method' => 'cash',
                'payment_date' => now()->toDateString(),
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('payments', 1);

        // Second payment exceeding remaining balance (100) is rejected
        $this->actingAs($user)
            ->post(route('work-orders.payments.store', $workOrder->id), [
                'type' => 'payment',
                'amount' => 150,
                'payment_method' => 'cash',
                'payment_date' => now()->toDateString(),
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('payments', 1);
    }

    public function test_payment_and_bad_debt_exceeding_balance_on_done_work_order_with_deferred_invoice_are_rejected(): void
    {
        [$tenant, $center, $user, $customer, $vehicle] = $this->createTenantContext();

        $workOrder = WorkOrder::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'code' => 'WO-PAY-002',
            'status' => WorkOrder::STATUS_DONE,
        ]);

        WorkOrderItem::create([
            'work_order_id' => $workOrder->id,
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'title' => 'Engine Overhaul',
            'qty' => 1,
            'unit_price' => 500,
            'min_price_snapshot' => 0.00,
            'status' => WorkOrderItem::STATUS_COMPLETED,
        ]);

        Invoice::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'work_order_id' => $workOrder->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-DEF-001',
            'issue_date' => now()->toDateString(),
            'supply_date' => now()->toDateString(),
            'due_date' => now()->addDays(15)->toDateString(),
            'subtotal' => 500,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total_amount' => 500,
            'paid_amount' => 0,
            'status' => 'issued',
        ]);

        // Reject payment exceeding balance (500)
        $this->actingAs($user)
            ->post(route('work-orders.payments.store', $workOrder->id), [
                'type' => 'payment',
                'amount' => 600,
                'payment_method' => 'cash',
                'payment_date' => now()->toDateString(),
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('amount');

        // Reject bad_debt exceeding balance (500)
        $this->actingAs($user)
            ->post(route('work-orders.payments.store', $workOrder->id), [
                'type' => 'bad_debt',
                'amount' => 501,
                'payment_date' => now()->toDateString(),
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('amount');

        // Pay full remaining balance (500)
        $this->actingAs($user)
            ->post(route('work-orders.payments.store', $workOrder->id), [
                'type' => 'payment',
                'amount' => 500,
                'payment_method' => 'cash',
                'payment_date' => now()->toDateString(),
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('payments', 1);

        // Even with deferred invoice (due_date !== null), any new payment when balance == 0 must be rejected
        $this->actingAs($user)
            ->post(route('work-orders.payments.store', $workOrder->id), [
                'type' => 'payment',
                'amount' => 50,
                'payment_method' => 'cash',
                'payment_date' => now()->toDateString(),
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('amount')
            ->assertSessionHas('error', __('payments.amount_exceeds_balance'));

        $this->assertDatabaseCount('payments', 1);
    }

    public function test_updating_existing_payment_validates_against_balance_plus_existing_amount(): void
    {
        [$tenant, $center, $user, $customer, $vehicle] = $this->createTenantContext();

        $workOrder = WorkOrder::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'code' => 'WO-PAY-003',
            'status' => WorkOrder::STATUS_OPEN,
        ]);

        WorkOrderItem::create([
            'work_order_id' => $workOrder->id,
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'title' => 'AC Repair',
            'qty' => 1,
            'unit_price' => 400,
            'min_price_snapshot' => 0.00,
            'status' => WorkOrderItem::STATUS_COMPLETED,
        ]);

        $payment = Payment::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'work_order_id' => $workOrder->id,
            'type' => PaymentType::PAYMENT,
            'amount' => 150,
            'payment_method' => 'cash',
            'payment_date' => now()->toDateString(),
            'created_by' => $user->id,
        ]);

        // Remaining balance is 250; updating existing 150 payment allows up to 400
        $this->actingAs($user)
            ->put(route('work-orders.payments.update', [$workOrder->id, $payment->id]), [
                'type' => 'payment',
                'amount' => 401,
                'payment_method' => 'cash',
                'payment_date' => now()->toDateString(),
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('amount');

        $this->assertEquals(150.0, (float) $payment->fresh()->amount);

        $this->actingAs($user)
            ->put(route('work-orders.payments.update', [$workOrder->id, $payment->id]), [
                'type' => 'payment',
                'amount' => 400,
                'payment_method' => 'cash',
                'payment_date' => now()->toDateString(),
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertEquals(400.0, (float) $payment->fresh()->amount);
    }
}
