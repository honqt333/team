<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Services\InvoiceService;
use App\Services\Optimization\TaxCalculator;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cross-sync tests: paying from the WorkOrder screen must reflect on the
 * linked invoice (and vice-versa). Before this fix, paying from the WO
 * left the invoice total_paid stale and the next page load showed a wrong
 * balance.
 */
class PaymentCrossSyncTest extends TestCase
{
    use RefreshDatabase;

    private function makeWoWithInvoice(float $total = 1000.00): array
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
            'code' => 'WO-TEST-001',
            'status' => 'completed',
            'currency_code' => 'SAR',
            'tax_enabled_snapshot' => true,
            'pricing_mode_snapshot' => 'exclusive',
            'tax_rate_snapshot' => 15.00,
            'total_incl_tax' => $total,
        ]);

        // Give the WO a real item so the recalculateTotals hook produces a
        // non-zero grand total (otherwise the invoice inherits 0).
        WorkOrderItem::create([
            'work_order_id' => $wo->id,
            'tenant_id' => $wo->tenant_id,
            'center_id' => $wo->center_id,
            'title' => 'Service',
            'qty' => 1,
            'unit_price' => $total / 1.15, // back out VAT so incl ≈ $total
            'is_taxable' => true,
            'tax_rate_snapshot' => 15.00,
            'status' => WorkOrderItem::STATUS_COMPLETED,
        ]);
        $wo->refresh();

        $svc = new InvoiceService(new TaxCalculator);
        $invoice = $svc->createFromWorkOrder($wo, null);

        return [$wo, $invoice];
    }

    /** @test */
    public function paying_from_invoice_also_links_to_work_order(): void
    {
        [$wo, $invoice] = $this->makeWoWithInvoice(1000.00);

        $svc = new PaymentService;
        $svc->recordPayment($invoice, [
            'amount' => 250.00,
            'payment_method' => 'cash',
        ]);

        $payment = Payment::where('invoice_id', $invoice->id)->first();
        $this->assertNotNull($payment, 'Payment should be created');
        $this->assertSame($wo->id, (int) $payment->work_order_id, 'Payment must also link back to WorkOrder');

        // Refresh and verify both views agree.
        $wo->refresh();
        $invoice->refresh();
        $this->assertSame(250.00, (float) $invoice->total_paid);
        $this->assertSame(250.00, (float) $wo->total_paid, 'WO accessor should match the linked invoice total');
    }

    /** @test */
    public function paying_from_work_order_links_to_invoice_and_recomputes_status(): void
    {
        [$wo, $invoice] = $this->makeWoWithInvoice(1000.00);

        $wo->payments()->create([
            'tenant_id' => $wo->tenant_id,
            'center_id' => $wo->center_id,
            'invoice_id' => $invoice->id,
            'type' => 'payment',
            'payment_method' => 'cash',
            'amount' => 400.00,
            'payment_date' => now(),
            'received_by' => auth()->id(),
        ]);
        $invoice->refresh()->updatePaymentStatus();

        $payment = Payment::where('work_order_id', $wo->id)->first();
        $this->assertSame($invoice->id, (int) $payment->invoice_id, 'Payment must also link to invoice');

        $invoice->refresh();
        $this->assertSame(400.00, (float) $invoice->total_paid);
        $this->assertSame('partial', $invoice->payment_status, "total_incl_tax={$invoice->total_incl_tax}, total_paid={$invoice->total_paid}");
    }

    /** @test */
    public function refund_on_wo_also_reduces_invoice_total_paid(): void
    {
        [$wo, $invoice] = $this->makeWoWithInvoice(1000.00);

        // Pay 500 normally
        $wo->payments()->create([
            'tenant_id' => $wo->tenant_id,
            'center_id' => $wo->center_id,
            'invoice_id' => $invoice->id,
            'type' => 'payment',
            'payment_method' => 'cash',
            'amount' => 500.00,
            'payment_date' => now(),
            'received_by' => auth()->id(),
        ]);
        $invoice->refresh()->updatePaymentStatus();
        $this->assertSame(500.00, (float) $invoice->total_paid);

        // Refund 100 — total_paid should drop to 400 on both sides.
        $wo->payments()->create([
            'tenant_id' => $wo->tenant_id,
            'center_id' => $wo->center_id,
            'invoice_id' => $invoice->id,
            'type' => 'refund',
            'payment_method' => 'cash',
            'amount' => 100.00,
            'payment_date' => now(),
            'received_by' => auth()->id(),
        ]);
        $invoice->refresh()->updatePaymentStatus();

        $invoice->refresh();
        $this->assertSame(400.00, (float) $invoice->total_paid, 'Refund must reduce invoice total_paid');
    }
}
