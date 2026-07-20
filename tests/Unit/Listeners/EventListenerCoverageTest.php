<?php

namespace Tests\Unit\Listeners;

use App\Events\Auth\LoginFailed;
use App\Events\Auth\LoginSuccessful;
use App\Events\Payment\PaymentRecorded;
use App\Events\WorkOrder\WorkOrderCreated;
use App\Events\WorkOrder\WorkOrderStatusChanged;
use App\Listeners\Auth\LogFailedLogin;
use App\Listeners\Auth\LogSuccessfulLogin;
use App\Listeners\Payment\UpdateInvoiceStatusOnPayment;
use App\Listeners\WorkOrder\LogActivityOnStatusChange;
use App\Listeners\WorkOrder\NotifyOwnerOnCreation;
use App\Models\Center;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventListenerCoverageTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Center $center;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->center = Center::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    public function test_log_activity_on_status_change_creates_activity_record()
    {
        $this->actingAs($this->user);

        $workOrder = WorkOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'status' => 'in_progress',
        ]);

        $listener = new LogActivityOnStatusChange();
        $event = new WorkOrderStatusChanged($workOrder, 'pending', 'in_progress', $this->user->id);

        $listener->handle($event);

        $this->assertDatabaseHas('work_order_activities', [
            'work_order_id' => $workOrder->id,
            'user_id' => $this->user->id,
            'action' => 'status_changed',
        ]);
    }

    public function test_notify_owner_on_creation_runs_without_exception()
    {
        $workOrder = WorkOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
        ]);

        $listener = new NotifyOwnerOnCreation();
        $event = new WorkOrderCreated($workOrder);

        $listener->handle($event);

        $this->assertTrue(true);
    }

    public function test_update_invoice_status_on_payment_updates_linked_invoice()
    {
        $customer = \App\Models\Customer::factory()->create(['tenant_id' => $this->tenant->id, 'center_id' => $this->center->id]);

        $invoice = Invoice::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-TEST-001',
            'issue_date' => now(),
            'supply_date' => now(),
            'status' => 'valid',
            'payment_status' => 'unpaid',
            'total_excl_tax' => 100.00,
            'total_tax' => 15.00,
            'total_incl_tax' => 115.00,
        ]);

        $payment = Payment::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'invoice_id' => $invoice->id,
            'type' => 'payment',
            'amount' => 115.00,
            'payment_date' => now(),
        ]);

        $listener = new UpdateInvoiceStatusOnPayment();
        $event = new PaymentRecorded($payment);

        $listener->handle($event);

        $invoice->refresh();
        $this->assertEquals('paid', $invoice->payment_status);
    }

    public function test_log_successful_login_runs_without_exception()
    {
        $listener = new LogSuccessfulLogin();
        $event = new LoginSuccessful($this->user, '127.0.0.1', 'PHPUnit');

        $listener->handle($event);

        $this->assertTrue(true);
    }

    public function test_log_failed_login_runs_without_exception()
    {
        $listener = new LogFailedLogin();
        $event = new LoginFailed('unknown@example.com', '127.0.0.1', 'PHPUnit');

        $listener->handle($event);

        $this->assertTrue(true);
    }
}
