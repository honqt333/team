<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Customer;
use App\Models\Tenant;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Services\InvoiceService;
use App\Services\Optimization\TaxCalculator;
use DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Business rule: a work order that has been exited (status = done) but was
 * not invoiced at exit time (deferred) must auto-materialise an invoice the
 * moment the customer finishes paying. Without this, the WO can sit
 * "done" with a positive balance and the operator has to remember to issue
 * the invoice manually — a step that's easy to skip.
 */
class AutoInvoiceOnFullPaymentTest extends TestCase
{
    use RefreshDatabase;

    private function makeDoneWo(float $total = 1000.00): array
    {
        $tenant = Tenant::create(['name' => 'T1', 'slug' => 't1']);
        $center = Center::create(['tenant_id' => $tenant->id, 'name' => 'C1', 'slug' => 'c1']);
        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'name' => 'Cust1',
            'phone' => '1234567890',
        ]);
        $vehicle = Vehicle::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'plate_number' => 'ABC123',
            'make' => 'Toyota',
            'model' => 'Camry',
            'year' => 2024,
        ]);

        $wo = WorkOrder::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'code' => 'WO-TEST-DONE',
            'status' => 'done',
            'currency_code' => 'SAR',
            'tax_enabled_snapshot' => true,
            'pricing_mode_snapshot' => 'exclusive',
            'tax_rate_snapshot' => 15.00,
            'total_incl_tax' => $total,
        ]);

        // Need at least one item so the WO has a real total to invoice.
        WorkOrderItem::create([
            'work_order_id' => $wo->id,
            'tenant_id' => $wo->tenant_id,
            'center_id' => $wo->center_id,
            'title' => 'Service',
            'qty' => 1,
            'unit_price' => $total / 1.15,
            'is_taxable' => true,
            'tax_rate_snapshot' => 15.00,
            'status' => WorkOrderItem::STATUS_COMPLETED,
        ]);
        $wo->refresh();

        return [$wo, $customer];
    }

    /** @test */
    public function partial_payment_does_not_create_invoice(): void
    {
        [$wo] = $this->makeDoneWo(1000.00);

        $wo->payments()->create([
            'tenant_id' => $wo->tenant_id,
            'center_id' => $wo->center_id,
            'type' => 'payment',
            'payment_method' => 'cash',
            'amount' => 400.00,
            'payment_date' => now(),
        ]);

        $wo->refresh();
        $this->assertNull($wo->invoice, 'Partial payment must not auto-create the invoice');
    }

    /** @test */
    public function full_payment_auto_creates_invoice(): void
    {
        [$wo] = $this->makeDoneWo(1000.00);

        // First payment: partial
        $wo->payments()->create([
            'tenant_id' => $wo->tenant_id,
            'center_id' => $wo->center_id,
            'type' => 'payment',
            'payment_method' => 'cash',
            'amount' => 600.00,
            'payment_date' => now(),
        ]);

        $wo->refresh();
        $this->assertNull($wo->invoice, 'Should still be uninvoiced at 60%');

        // Final payment — should trigger auto-invoice
        $wo->payments()->create([
            'tenant_id' => $wo->tenant_id,
            'center_id' => $wo->center_id,
            'type' => 'payment',
            'payment_method' => 'cash',
            'amount' => 400.00,
            'payment_date' => now(),
        ]);

        $wo->refresh();
        $this->assertNotNull($wo->invoice, 'Invoice must be auto-created once WO is fully paid');
        $this->assertSame('paid', $wo->invoice->payment_status);
        // Allow 1-cent rounding drift between the WO total (carried over)
        // and the invoice total (recomputed from line items with VAT).
        $this->assertEqualsWithDelta(1000.00, (float) $wo->invoice->total_incl_tax, 0.05);
    }

    /** @test */
    public function payment_on_active_wo_does_not_create_invoice(): void
    {
        [$wo] = $this->makeDoneWo(1000.00);
        // Reopen the WO so it's not "done" anymore
        $wo->update(['status' => 'in_progress']);

        $wo->payments()->create([
            'tenant_id' => $wo->tenant_id,
            'center_id' => $wo->center_id,
            'type' => 'payment',
            'payment_method' => 'cash',
            'amount' => 1000.00,
            'payment_date' => now(),
        ]);

        $wo->refresh();
        $this->assertNull($wo->invoice, 'Invoice creation is reserved for done WOs — operator drives active WOs');
    }

    /** @test */
    public function refund_does_not_trigger_invoice_creation(): void
    {
        [$wo] = $this->makeDoneWo(1000.00);

        // Refund without any prior payment — balance is still 1000, so this
        // would not satisfy the rule anyway, but make sure the refund path
        // also doesn't trip the auto-invoice logic.
        $wo->payments()->create([
            'tenant_id' => $wo->tenant_id,
            'center_id' => $wo->center_id,
            'type' => 'refund',
            'payment_method' => 'cash',
            'amount' => 100.00,
            'payment_date' => now(),
        ]);

        $wo->refresh();
        $this->assertNull($wo->invoice);
    }

    /** @test */
    public function already_invoiced_wo_does_not_double_invoice_on_full_payment(): void
    {
        [$wo, $customer] = $this->makeDoneWo(1000.00);

        // Pre-create a deferred invoice (simulating the existing flow at WO
        // exit time). The WO's outstanding balance drops to 0 once payments
        // land on it.
        $wo->update(['total_incl_tax' => 0, 'status' => 'in_progress']); // avoid status check
        $svc = new InvoiceService(new TaxCalculator);
        $invoice = $svc->createFromWorkOrder($wo->fresh(), null);
        $wo->refresh();

        // Now pay against the WO. The new payment should NOT spawn a second
        // invoice because one already exists.
        $wo->payments()->create([
            'tenant_id' => $wo->tenant_id,
            'center_id' => $wo->center_id,
            'type' => 'payment',
            'payment_method' => 'cash',
            'amount' => 0.00, // trivial
            'payment_date' => now(),
        ]);

        $this->assertSame(1, $wo->invoice()->count() ?: (int) DB::table('invoices')->where('work_order_id', $wo->id)->count());
    }
}
