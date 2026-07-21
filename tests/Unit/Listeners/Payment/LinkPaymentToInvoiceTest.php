<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners\Payment;

use App\Events\Payment\PaymentRecorded;
use App\Listeners\Payment\LinkPaymentToInvoice;
use App\Models\Center;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LinkPaymentToInvoiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_links_payment_to_existing_invoice(): void
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
            'code' => 'WO-LINK-1',
            'status' => WorkOrder::STATUS_IN_PROGRESS,
        ]);

        $invoice = Invoice::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'work_order_id' => $workOrder->id,
            'invoice_number' => 'INV-LINK-1',
            'issue_date' => now(),
            'supply_date' => now(),
            'status' => 'draft',
            'payment_status' => 'unpaid',
            'total_excl_tax' => 100,
            'total_tax' => 0,
            'total_incl_tax' => 100,
            'total_taxable_amount' => 100,
            'total_paid' => 0,
        ]);

        $payment = Payment::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'work_order_id' => $workOrder->id,
            'amount' => 50,
            'payment_date' => now(),
            'payment_method' => 'cash',
            'type' => 'payment',
            'received_by' => $user->id,
        ]);

        // The PaymentObserver's created() hook should have already linked
        // the payment to the invoice (since the listener is registered for
        // PaymentRecorded). We test that the explicit listener call is
        // idempotent and doesn't break the link.
        $payment->refresh();
        $this->assertEquals($invoice->id, $payment->invoice_id, 'Payment should be linked after creation');

        $listener = new LinkPaymentToInvoice;
        $listener->handle(new PaymentRecorded($payment));

        $payment->refresh();
        $this->assertEquals($invoice->id, $payment->invoice_id, 'Payment should remain linked to existing invoice');
    }

    public function test_skips_payment_without_work_order(): void
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
            'amount' => 50,
            'payment_date' => now(),
            'payment_method' => 'cash',
            'type' => 'payment',
            'received_by' => $user->id,
        ]);

        $listener = new LinkPaymentToInvoice;
        $listener->handle(new PaymentRecorded($payment));

        $payment->refresh();
        $this->assertNull($payment->invoice_id, 'Standalone payment should not be linked');
    }

    public function test_skips_when_invoice_already_set(): void
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
            'code' => 'WO-LINK-3',
            'status' => WorkOrder::STATUS_IN_PROGRESS,
        ]);

        $invoiceA = Invoice::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'work_order_id' => $workOrder->id,
            'invoice_number' => 'INV-A',
            'issue_date' => now(),
            'supply_date' => now(),
            'status' => 'draft',
            'payment_status' => 'unpaid',
            'total_excl_tax' => 0,
            'total_tax' => 0,
            'total_incl_tax' => 0,
            'total_taxable_amount' => 0,
            'total_paid' => 0,
        ]);

        $invoiceB = Invoice::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-B',
            'issue_date' => now(),
            'supply_date' => now(),
            'status' => 'draft',
            'payment_status' => 'unpaid',
            'total_excl_tax' => 0,
            'total_tax' => 0,
            'total_incl_tax' => 0,
            'total_taxable_amount' => 0,
            'total_paid' => 0,
        ]);

        $payment = Payment::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'work_order_id' => $workOrder->id,
            'invoice_id' => $invoiceB->id, // Already linked to different invoice
            'amount' => 50,
            'payment_date' => now(),
            'payment_method' => 'cash',
            'type' => 'payment',
            'received_by' => $user->id,
        ]);

        $listener = new LinkPaymentToInvoice;
        $listener->handle(new PaymentRecorded($payment));

        $payment->refresh();
        $this->assertEquals($invoiceB->id, $payment->invoice_id, 'Pre-existing invoice_id must not be overridden');
    }
}
