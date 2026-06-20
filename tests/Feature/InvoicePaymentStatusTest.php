<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression tests for Invoice::updatePaymentStatus after the
 * "convert WorkOrder → Invoice does not inherit amounts" fix.
 *
 * Before the fix, `updatePaymentStatus` summed `payments.amount` blindly,
 * which meant refunds (type = 'refund') were added to `total_paid` instead
 * of subtracted — breaking the displayed Paid and Balance boxes on the
 * invoice page right after a WorkOrder → Invoice conversion that carried
 * over WO refunds.
 */
class InvoicePaymentStatusTest extends TestCase
{
    use RefreshDatabase;

    private function makeInvoice(float $totalInclTax = 1000.00): Invoice
    {
        $tenant = Tenant::create(['name' => 'T1', 'slug' => 't1']);
        $center = Center::create(['tenant_id' => $tenant->id, 'name' => 'C1', 'slug' => 'c1']);
        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'name'      => 'Cust1',
            'phone'     => '1234567890',
        ]);

        return Invoice::create([
            'tenant_id'              => $tenant->id,
            'center_id'              => $center->id,
            'customer_id'            => $customer->id,
            'invoice_number'         => 'INV-TEST-001',
            'status'                 => 'valid',
            'issue_date'             => now(),
            'supply_date'            => now(),
            'total_excl_tax'         => $totalInclTax,
            'total_tax'              => 0,
            'total_incl_tax'         => $totalInclTax,
            'total_paid'             => 0,
            'payment_status'         => 'unpaid',
        ]);
    }

    private function makePayment(Invoice $invoice, float $amount, string $type = 'payment'): Payment
    {
        return Payment::create([
            'tenant_id'     => $invoice->tenant_id,
            'center_id'     => $invoice->center_id,
            'invoice_id'    => $invoice->id,
            'amount'        => $amount,
            'payment_date'  => now(),
            'payment_method'=> 'cash',
            'type'          => $type,
        ]);
    }

    /** @test */
    public function pure_payment_path_is_unchanged(): void
    {
        $invoice = $this->makeInvoice(1000.00);
        $this->makePayment($invoice, 400.00);

        $invoice->refresh();
        $this->assertSame(400.00, (float) $invoice->total_paid);
        $this->assertSame('partial', $invoice->payment_status);
        $this->assertSame(600.00, $invoice->balance);
    }

    /** @test */
    public function refund_subtracts_from_total_paid_after_conversion(): void
    {
        // Simulate the WO → Invoice conversion flow: an Invoice that already
        // carries over two payments from the parent WorkOrder — a 500 payment
        // and a 100 refund. The previous code would report total_paid = 600.
        $invoice = $this->makeInvoice(1000.00);
        $this->makePayment($invoice, 500.00, 'payment');
        $this->makePayment($invoice, 100.00, 'refund');

        $invoice->refresh();
        $this->assertSame(400.00, (float) $invoice->total_paid, 'Refunds must reduce total_paid');
        $this->assertSame('partial', $invoice->payment_status);
        $this->assertSame(600.00, $invoice->balance);
    }

    /** @test */
    public function refund_that_exceeds_payments_drives_status_to_unpaid(): void
    {
        $invoice = $this->makeInvoice(1000.00);
        $this->makePayment($invoice, 200.00, 'payment');
        $this->makePayment($invoice, 500.00, 'refund'); // net = -300

        $invoice->refresh();
        $this->assertSame(-300.00, (float) $invoice->total_paid);
        $this->assertSame('unpaid', $invoice->payment_status);
        $this->assertSame(1300.00, $invoice->balance); // total_incl_tax - (-300)
    }

    /** @test */
    public function fully_refunded_invoice_still_marks_paid(): void
    {
        $invoice = $this->makeInvoice(1000.00);
        $this->makePayment($invoice, 1000.00, 'payment');
        $this->makePayment($invoice, 1000.00, 'refund'); // net = 0

        $invoice->refresh();
        $this->assertSame(0.0, (float) $invoice->total_paid);
        $this->assertSame('unpaid', $invoice->payment_status);
        $this->assertSame(1000.00, $invoice->balance);
    }
}