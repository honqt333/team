<?php

namespace Tests\Unit\Listeners\Payment;

use App\Events\Payment\PaymentRecorded;
use App\Listeners\Payment\HandleAutoInvoiceOnDoneWorkOrder;
use App\Models\Center;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HandleAutoInvoiceOnDoneWorkOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_invoice_when_wo_done_and_fully_paid(): void
    {
        $tenant = Tenant::factory()->create();
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
        ]);
        $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);
        $vehicle = Vehicle::factory()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
        ]);

        $workOrder = WorkOrder::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'code' => 'WO-AUTO-1',
            'status' => WorkOrder::STATUS_DONE,
            'tax_enabled_snapshot' => false,
        ]);

        $workOrder->items()->create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'title' => 'Full Service',
            'qty' => 1,
            'unit_price' => 100,
            'status' => 'completed',
        ]);

        $payment = Payment::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'work_order_id' => $workOrder->id,
            'amount' => 100,
            'payment_date' => now(),
            'payment_method' => 'cash',
            'type' => 'payment',
            'received_by' => $user->id,
        ]);

        $listener = app(HandleAutoInvoiceOnDoneWorkOrder::class);
        $listener->handle(new PaymentRecorded($payment));

        $workOrder->refresh();
        $this->assertNotNull($workOrder->invoice, 'Invoice should be auto-created for fully paid done WO');
        $this->assertEquals(100, (float) $workOrder->invoice->total_paid);
        $this->assertEquals('paid', $workOrder->invoice->payment_status);
    }

    public function test_skips_refund_payments(): void
    {
        $tenant = Tenant::factory()->create();
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
        ]);
        $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);
        $vehicle = Vehicle::factory()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
        ]);

        $workOrder = WorkOrder::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'code' => 'WO-AUTO-REFUND',
            'status' => WorkOrder::STATUS_DONE,
        ]);

        $payment = Payment::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'work_order_id' => $workOrder->id,
            'amount' => 50,
            'payment_date' => now(),
            'payment_method' => 'cash',
            'type' => 'refund',
            'received_by' => $user->id,
        ]);

        $listener = app(HandleAutoInvoiceOnDoneWorkOrder::class);
        $listener->handle(new PaymentRecorded($payment));

        $workOrder->refresh();
        $this->assertNull($workOrder->invoice, 'Refund payments must not trigger invoice creation');
    }

    public function test_skips_when_wo_not_done(): void
    {
        $tenant = Tenant::factory()->create();
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
        ]);
        $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);
        $vehicle = Vehicle::factory()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
        ]);

        $workOrder = WorkOrder::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'code' => 'WO-AUTO-INPROG',
            'status' => WorkOrder::STATUS_IN_PROGRESS,
        ]);

        $payment = Payment::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'work_order_id' => $workOrder->id,
            'amount' => 100,
            'payment_date' => now(),
            'payment_method' => 'cash',
            'type' => 'payment',
            'received_by' => $user->id,
        ]);

        $listener = app(HandleAutoInvoiceOnDoneWorkOrder::class);
        $listener->handle(new PaymentRecorded($payment));

        $workOrder->refresh();
        $this->assertNull($workOrder->invoice, 'In-progress WO must not auto-invoice');
    }

    public function test_skips_when_wo_not_fully_paid(): void
    {
        $tenant = Tenant::factory()->create();
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
        ]);
        $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);
        $vehicle = Vehicle::factory()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
        ]);

        $workOrder = WorkOrder::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'code' => 'WO-AUTO-PARTIAL',
            'status' => WorkOrder::STATUS_DONE,
            'tax_enabled_snapshot' => false,
        ]);

        $workOrder->items()->create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'title' => 'Big Service',
            'qty' => 1,
            'unit_price' => 200,
            'status' => 'completed',
        ]);

        // Only pay half
        $payment = Payment::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'work_order_id' => $workOrder->id,
            'amount' => 100,
            'payment_date' => now(),
            'payment_method' => 'cash',
            'type' => 'payment',
            'received_by' => $user->id,
        ]);

        $listener = app(HandleAutoInvoiceOnDoneWorkOrder::class);
        $listener->handle(new PaymentRecorded($payment));

        $workOrder->refresh();
        $this->assertNull($workOrder->invoice, 'Partially paid WO must not auto-invoice');
    }

    public function test_skips_when_standalone_payment(): void
    {
        $tenant = Tenant::factory()->create();
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
        ]);

        $payment = Payment::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'work_order_id' => null,
            'amount' => 100,
            'payment_date' => now(),
            'payment_method' => 'cash',
            'type' => 'payment',
            'received_by' => $user->id,
        ]);

        $listener = app(HandleAutoInvoiceOnDoneWorkOrder::class);
        $listener->handle(new PaymentRecorded($payment));

        // No exception = success. Nothing to assert beyond that.
        $this->assertTrue(true);
    }
}
