<?php

namespace Tests\Feature\TenantIsolation;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Payment;
use App\Models\Quote;
use App\Models\Service;
use App\Models\Vehicle;

/**
 * Contract tests for finance/invoice domain: an invoice in tenant B (with
 * its child lines, payments, and source quote) must never be queryable
 * by a user authenticated as tenant A.
 */
class InvoicePaymentTenantIsolationTest extends TenantIsolationTestCase
{
    /** @test */
    public function invoice_in_tenant_b_is_not_visible_to_tenant_a(): void
    {
        $customerB = Customer::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'center_id' => $this->centerB->id,
            'phone' => '+966500000010',
        ]);
        $vehicleB = Vehicle::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'center_id' => $this->centerB->id,
            'customer_id' => $customerB->id,
            'plate_number' => 'BBB-001',
        ]);

        $invoiceB = Invoice::create([
            'tenant_id' => $this->tenantB->id,
            'center_id' => $this->centerB->id,
            'customer_id' => $customerB->id,
            'vehicle_id' => $vehicleB->id,
            'invoice_number' => 'INV-B-0001',
            'issue_date' => now()->toDateString(),
            'supply_date' => now()->toDateString(),
            'status' => 'draft',
            'currency_code' => 'SAR',
            'tax_enabled_snapshot' => true,
            'tax_rate_snapshot' => 15,
            'pricing_mode_snapshot' => 'exclusive',
            'total_tax' => 15,
            'total_excl_tax' => 100,
            'total_incl_tax' => 115,
        ]);

        $this->actingAs($this->userA);

        $this->assertNull(Invoice::query()->find($invoiceB->id));
        $this->assertCount(0, Invoice::query()->where('id', $invoiceB->id)->get());
    }

    /** @test */
    public function payment_in_tenant_b_is_not_visible_to_tenant_a(): void
    {
        $invoiceB = $this->makeInvoiceInB();

        $paymentB = Payment::create([
            'tenant_id' => $this->tenantB->id,
            'center_id' => $this->centerB->id,
            'invoice_id' => $invoiceB->id,
            'amount' => 100,
            'type' => 'payment',
            'payment_method' => 'cash',
            'payment_date' => now()->toDateString(),
        ]);

        $this->actingAs($this->userA);

        $this->assertNull(Payment::query()->find($paymentB->id));
    }

    /** @test */
    public function quote_in_tenant_b_is_not_visible_to_tenant_a(): void
    {
        $customerB = Customer::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'center_id' => $this->centerB->id,
            'phone' => '+966500000020',
        ]);
        $vehicleB = Vehicle::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'center_id' => $this->centerB->id,
            'customer_id' => $customerB->id,
            'plate_number' => 'BBB-002',
        ]);

        $quoteB = Quote::create([
            'tenant_id' => $this->tenantB->id,
            'center_id' => $this->centerB->id,
            'customer_id' => $customerB->id,
            'vehicle_id' => $vehicleB->id,
            'code' => 'Q-B-0001',
            'status' => 'draft',
            'currency_code' => 'SAR',
            'subtotal' => 0,
            'total' => 0,
            'total_discount' => 0,
        ]);

        $this->actingAs($this->userA);

        $this->assertNull(Quote::query()->find($quoteB->id));
    }

    /** @test */
    public function invoice_lines_in_tenant_b_are_not_visible_to_tenant_a(): void
    {
        $invoiceB = $this->makeInvoiceInB();

        $lineB = InvoiceLine::create([
            'tenant_id' => $this->tenantB->id,
            'center_id' => $this->centerB->id,
            'invoice_id' => $invoiceB->id,
            'description' => 'Service B',
            'qty' => 1,
            'unit_price' => 100,
            'is_taxable' => true,
            'tax_rate_snapshot' => 15,
            'line_total_excl_tax' => 100,
            'line_total_incl_tax' => 115,
        ]);

        $this->actingAs($this->userA);

        $this->assertNull(InvoiceLine::query()->find($lineB->id));
    }

    /** @test */
    public function service_catalog_in_tenant_b_is_not_visible_to_tenant_a(): void
    {
        $serviceB = Service::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'center_id' => $this->centerB->id,
            'name_ar' => 'خدمة B',
        ]);

        $this->actingAs($this->userA);

        $this->assertNull(Service::query()->find($serviceB->id));
    }

    protected function createRecordInTenantB(): mixed
    {
        return null;
    }

    private function makeInvoiceInB(): Invoice
    {
        $customerB = Customer::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'center_id' => $this->centerB->id,
            'phone' => '+966500009'.random_int(100, 999),
        ]);
        $vehicleB = Vehicle::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'center_id' => $this->centerB->id,
            'customer_id' => $customerB->id,
            'plate_number' => 'BBB-'.random_int(100, 999),
        ]);

        return Invoice::create([
            'tenant_id' => $this->tenantB->id,
            'center_id' => $this->centerB->id,
            'customer_id' => $customerB->id,
            'vehicle_id' => $vehicleB->id,
            'invoice_number' => 'INV-B-'.random_int(1000, 9999),
            'issue_date' => now()->toDateString(),
            'supply_date' => now()->toDateString(),
            'status' => 'draft',
            'currency_code' => 'SAR',
            'tax_enabled_snapshot' => true,
            'tax_rate_snapshot' => 15,
            'pricing_mode_snapshot' => 'exclusive',
            'total_tax' => 15,
            'total_excl_tax' => 100,
            'total_incl_tax' => 115,
        ]);
    }
}
