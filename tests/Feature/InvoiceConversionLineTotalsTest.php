<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Customer;
use App\Models\Tenant;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Services\InvoiceService;
use App\Services\Optimization\TaxCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression test for the second leg of the
 * "convert WorkOrder → Invoice does not inherit amounts" bug.
 *
 * The cost box on /app/invoices/{id} showed the wrong unit price + discount
 * because InvoiceService::createFromWorkOrder did not carry WorkOrderItem's
 * discount_amount over, and trusted WO-side line_total_excl_tax /
 * line_total_incl_tax columns that are stored as 0.
 */
class InvoiceConversionLineTotalsTest extends TestCase
{
    use RefreshDatabase;

    private function makeWoWithItem(array $itemOverrides): array
    {
        $tenant = Tenant::create(['name' => 'T1', 'slug' => 't1']);
        $center = Center::create(['tenant_id' => $tenant->id, 'name' => 'C1', 'slug' => 'c1']);
        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'name'      => 'Cust1',
            'phone'     => '1234567890',
        ]);

        $vehicle = Vehicle::create([
            'tenant_id'    => $tenant->id,
            'center_id'    => $center->id,
            'customer_id'  => $customer->id,
            'plate_number' => 'ABC123',
            'make'         => 'Toyota',
            'model'        => 'Camry',
            'year'         => 2024,
        ]);

        $wo = WorkOrder::create(array_merge([
            'tenant_id'              => $tenant->id,
            'center_id'              => $center->id,
            'customer_id'            => $customer->id,
            'vehicle_id'             => $vehicle->id,
            'code'                   => 'WO-TEST-001',
            'status'                 => 'completed',
            'currency_code'          => 'SAR',
            'tax_enabled_snapshot'   => true,
            'pricing_mode_snapshot'  => 'inclusive',
            'tax_rate_snapshot'      => 15.00,
        ], $itemOverrides['work_order'] ?? []));

        $item = WorkOrderItem::create(array_merge([
            'work_order_id'     => $wo->id,
            'tenant_id'         => $wo->tenant_id,
            'center_id'         => $wo->center_id,
            'title'             => 'بدون الخرط',
            'qty'               => 1,
            'unit_price'        => 250,
            'discount_type'     => 'fixed',
            'discount_value'    => 120,
            'discount_amount'   => 120,
            'final_unit_price'  => 130,
            'is_taxable'        => true,
            'tax_rate_snapshot' => 15.00,
            'line_total'        => 130,
            'status'            => WorkOrderItem::STATUS_COMPLETED,
        ], $itemOverrides['item'] ?? []));

        return [$wo, $item];
    }

    /** @test */
    public function discount_amount_is_carried_over_and_line_totals_are_recomputed(): void
    {
        [$wo, $item] = $this->makeWoWithItem([]);
        $this->assertNotNull($item->id, 'Item not saved');
        $this->assertGreaterThan(0, $wo->items()->count(), 'WO has no items');

        $svc = new InvoiceService(new TaxCalculator());
        try {
            $invoice = $svc->createFromWorkOrder($wo, null);
        } catch (\Throwable $t) {
            $this->fail('createFromWorkOrder threw: ' . $t->getMessage() . ' at ' . $t->getFile() . ':' . $t->getLine());
        }

        $line = $invoice->lines->first();
        if (!$line) {
            $this->fail('Invoice lines empty. WO items count: ' . $wo->items()->count() . ', Item ID: ' . $item->id . ', WO items collection: ' . $wo->items->count());
        }
        $this->assertSame(250.00, (float) $line->unit_price);
        $this->assertSame(120.00, (float) $line->discount_amount);
        $this->assertSame(113.04, (float) $line->line_total_excl_tax);
        $this->assertSame(130.00, (float) $line->line_total_incl_tax);
    }

    /** @test */
    public function line_totals_match_when_legacy_wo_columns_are_zero(): void
    {
        // Reproduces the exact Invoice #14 scenario: WO side stored
        // line_total_excl_tax / line_total_incl_tax as 0 even though
        // discount_amount was correct.
        [$wo, $item] = $this->makeWoWithItem([]);
        $item->update(['line_total_excl_tax' => 0, 'line_total_incl_tax' => 0]);

        $svc = new InvoiceService(new TaxCalculator());
        $invoice = $svc->createFromWorkOrder($wo, null);

        $line = $invoice->lines->first();
        // Service must NOT inherit the 0 — it must recompute from
        // qty * unit_price - discount_amount.
        $this->assertSame(120.00, (float) $line->discount_amount);
        $this->assertSame(113.04, (float) $line->line_total_excl_tax);
        $this->assertSame(130.00, (float) $line->line_total_incl_tax);
    }
}