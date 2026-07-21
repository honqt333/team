<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression tests for the null-safe label accessors on Invoice
 * and Payment. Pin the bug fixed in 2026-07-06: when an invoice
 * is created with payment_status = null (the column default),
 * serialising it through getPaymentStatusLabelAttribute() crashed
 * with a TypeError because the match() default branch returned
 * null while the accessor was typed as string.
 */
class LabelAccessorNullSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_with_null_payment_status_does_not_throw_type_error(): void
    {
        $tenant = Tenant::factory()->create();
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);
        $customer = Customer::factory()->create(['tenant_id' => $tenant->id, 'center_id' => $center->id]);

        $invoice = Invoice::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'code' => 'INV-001',
            'invoice_number' => '001',
            'issue_date' => now()->format('Y-m-d'),
            'supply_date' => now()->format('Y-m-d'),
            'subtotal' => 100,
            'tax_amount' => 15,
            'total' => 115,
            'balance' => 115,
            'status' => 'draft',
            // payment_status intentionally omitted → null
        ]);

        // The accessor must not throw a TypeError, even though
        // the underlying column is null.
        $this->assertSame('', $invoice->payment_status_label);
    }

    public function test_invoice_with_known_payment_status_returns_translated_label(): void
    {
        $tenant = Tenant::factory()->create();
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);
        $customer = Customer::factory()->create(['tenant_id' => $tenant->id, 'center_id' => $center->id]);

        $invoice = new Invoice;
        $invoice->tenant_id = $tenant->id;
        $invoice->center_id = $center->id;
        $invoice->customer_id = $customer->id;
        $invoice->code = 'INV-002';
        $invoice->invoice_number = '002';
        $invoice->issue_date = now()->format('Y-m-d');
        $invoice->supply_date = now()->format('Y-m-d');
        $invoice->subtotal = 100;
        $invoice->tax_amount = 15;
        $invoice->total = 115;
        $invoice->balance = 0;
        $invoice->status = 'draft';
        $invoice->payment_status = 'paid'; // bypass the CHECK constraint

        // The accessor must return a non-empty translated label.
        $this->assertNotEmpty($invoice->payment_status_label);
    }

    public function test_payment_with_null_payment_method_does_not_throw_type_error(): void
    {
        $tenant = Tenant::factory()->create();
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);
        $customer = Customer::factory()->create(['tenant_id' => $tenant->id, 'center_id' => $center->id]);
        $invoice = Invoice::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'code' => 'INV-003',
            'invoice_number' => '003',
            'issue_date' => now()->format('Y-m-d'),
            'supply_date' => now()->format('Y-m-d'),
            'subtotal' => 0,
            'tax_amount' => 0,
            'total' => 0,
            'balance' => 0,
            'status' => 'draft',
        ]);

        $payment = Payment::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'invoice_id' => $invoice->id,
            'amount' => 0,
            'payment_date' => now()->format('Y-m-d'),
            // payment_method intentionally omitted → null
        ]);

        $this->assertSame('', $payment->payment_method_label);
    }
}
