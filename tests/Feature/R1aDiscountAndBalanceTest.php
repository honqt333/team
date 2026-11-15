<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PaymentType;
use App\Models\Center;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Payment;
use App\Models\Quote;
use App\Models\QuoteLine;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class R1aDiscountAndBalanceTest extends TestCase
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
            'crm.invoices.create',
            'crm.invoices.view',
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
            'crm.invoices.create',
            'crm.invoices.view',
        ]);

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'type' => 'individual',
            'name' => 'R1A Customer',
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

    public function test_work_order_item_and_invoice_service_discount_scales_with_qty_1_2_5(): void
    {
        [$tenant, $center, $user, $customer, $vehicle] = $this->createTenantContext();
        $this->actingAs($user);

        foreach ([1, 2, 5] as $qty) {
            // Fixed discount: 100 unit price, 15/unit discount => 85 final unit price
            $woFixed = WorkOrder::create([
                'tenant_id' => $tenant->id,
                'center_id' => $center->id,
                'customer_id' => $customer->id,
                'vehicle_id' => $vehicle->id,
                'code' => "WO-FIX-{$qty}",
                'status' => WorkOrder::STATUS_OPEN,
            ]);

            $itemFixed = WorkOrderItem::create([
                'work_order_id' => $woFixed->id,
                'tenant_id' => $tenant->id,
                'center_id' => $center->id,
                'title' => 'Fixed Discount Service',
                'qty' => $qty,
                'unit_price' => 100,
                'discount_type' => 'fixed',
                'discount_value' => 15,
                'is_taxable' => false,
                'tax_rate_snapshot' => 0,
            ]);

            $this->assertEquals(15 * $qty, (float) $itemFixed->discount_amount);
            $this->assertEquals(85.0, (float) $itemFixed->final_unit_price);
            $this->assertEquals(85 * $qty, (float) $itemFixed->line_total);

            $woFixed->recalculateTotals();
            $woFixed->refresh();
            $this->assertEquals(15 * $qty, (float) $woFixed->total_discount);
            $this->assertEquals(85 * $qty, (float) $woFixed->total_excl_tax);
            $this->assertEquals(85 * $qty, (float) $woFixed->total_incl_tax);

            $invFixed = app(InvoiceService::class)->createFromWorkOrder($woFixed, $user);
            $this->assertEquals(85 * $qty, (float) $invFixed->total_incl_tax);
            $this->assertEquals(15 * $qty, (float) $invFixed->lines()->sum('discount_amount'));

            // Percentage discount: 200 unit price, 10% discount => 20/unit => 180 final unit price
            $woPct = WorkOrder::create([
                'tenant_id' => $tenant->id,
                'center_id' => $center->id,
                'customer_id' => $customer->id,
                'vehicle_id' => $vehicle->id,
                'code' => "WO-PCT-{$qty}",
                'status' => WorkOrder::STATUS_OPEN,
            ]);

            $itemPct = WorkOrderItem::create([
                'work_order_id' => $woPct->id,
                'tenant_id' => $tenant->id,
                'center_id' => $center->id,
                'title' => 'Percent Discount Service',
                'qty' => $qty,
                'unit_price' => 200,
                'discount_type' => 'percentage',
                'discount_value' => 10,
                'is_taxable' => false,
                'tax_rate_snapshot' => 0,
            ]);

            $this->assertEquals(20 * $qty, (float) $itemPct->discount_amount);
            $this->assertEquals(180.0, (float) $itemPct->final_unit_price);
            $this->assertEquals(180 * $qty, (float) $itemPct->line_total);

            $woPct->recalculateTotals();
            $woPct->refresh();
            $this->assertEquals(20 * $qty, (float) $woPct->total_discount);
            $this->assertEquals(180 * $qty, (float) $woPct->total_excl_tax);
            $this->assertEquals(180 * $qty, (float) $woPct->total_incl_tax);

            $invPct = app(InvoiceService::class)->createFromWorkOrder($woPct, $user);
            $this->assertEquals(180 * $qty, (float) $invPct->total_incl_tax);
            $this->assertEquals(20 * $qty, (float) $invPct->lines()->sum('discount_amount'));
        }
    }

    public function test_quote_line_discount_scales_with_qty_1_2_5(): void
    {
        [$tenant, $center, $user, $customer, $vehicle] = $this->createTenantContext();
        $this->actingAs($user);

        foreach ([1, 2, 5] as $qty) {
            $quote = Quote::create([
                'tenant_id' => $tenant->id,
                'center_id' => $center->id,
                'customer_id' => $customer->id,
                'vehicle_id' => $vehicle->id,
                'code' => "QT-R1A-{$qty}",
                'status' => 'draft',
            ]);

            $lineFixed = QuoteLine::create([
                'quote_id' => $quote->id,
                'tenant_id' => $tenant->id,
                'center_id' => $center->id,
                'description' => 'Quote Fixed',
                'qty' => $qty,
                'unit_price' => 100,
                'discount_type' => 'fixed',
                'discount_value' => 15,
                'is_taxable' => false,
                'tax_rate_snapshot' => 0,
            ]);

            $linePct = QuoteLine::create([
                'quote_id' => $quote->id,
                'tenant_id' => $tenant->id,
                'center_id' => $center->id,
                'description' => 'Quote Pct',
                'qty' => $qty,
                'unit_price' => 200,
                'discount_type' => 'percentage',
                'discount_value' => 10,
                'is_taxable' => false,
                'tax_rate_snapshot' => 0,
            ]);

            $this->assertEquals(15 * $qty, (float) $lineFixed->discount_amount);
            $this->assertEquals(85 * $qty, (float) $lineFixed->line_total_excl_tax);

            $this->assertEquals(20 * $qty, (float) $linePct->discount_amount);
            $this->assertEquals(180 * $qty, (float) $linePct->line_total_excl_tax);

            $quote->recalculateTotals();
            $quote->save();
            $quote->refresh();

            $this->assertEquals((15 + 20) * $qty, (float) $quote->total_discount);
            $this->assertEquals((85 + 180) * $qty, (float) $quote->total_incl_tax);
        }
    }

    public function test_previously_issued_invoice_values_are_not_mutated(): void
    {
        [$tenant, $center, $user, $customer, $vehicle] = $this->createTenantContext();
        $this->actingAs($user);

        $workOrder = WorkOrder::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'code' => 'WO-HIST-001',
            'status' => WorkOrder::STATUS_OPEN,
        ]);

        // Historical issued invoice with frozen legacy values
        $invoice = Invoice::forceCreate([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'work_order_id' => $workOrder->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-HIST-001',
            'status' => 'valid',
            'total_excl_tax' => 270.00,
            'total_tax' => 40.50,
            'total_incl_tax' => 310.50,
            'issue_date' => now()->subDays(10),
            'supply_date' => now()->subDays(10)->toDateString(),
        ]);

        $line = InvoiceLine::forceCreate([
            'invoice_id' => $invoice->id,
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'description' => 'Historical Service',
            'qty' => 3,
            'unit_price' => 100.00,
            'discount_type' => 'percentage',
            'discount_value' => 10.00,
            'discount_amount' => 10.00,
            'line_total_excl_tax' => 270.00,
            'tax_rate_snapshot' => 15.00,
            'tax_amount' => 40.50,
            'line_total_incl_tax' => 310.50,
        ]);

        $invoice->refresh();
        $line->refresh();

        $this->assertEquals(310.50, (float) $invoice->total_incl_tax);
        $this->assertEquals(10.00, (float) $line->discount_amount);
    }

    public function test_bad_debt_is_not_double_subtracted_in_balance_sql_or_print_payments(): void
    {
        [$tenant, $center, $user, $customer, $vehicle] = $this->createTenantContext();
        $this->actingAs($user);

        // Work order total = 500
        $workOrder = WorkOrder::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'code' => 'WO-BD-001',
            'status' => WorkOrder::STATUS_OPEN,
        ]);

        WorkOrderItem::create([
            'work_order_id' => $workOrder->id,
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'title' => 'Major Repair',
            'qty' => 1,
            'unit_price' => 500,
            'is_taxable' => false,
            'tax_rate_snapshot' => 0,
        ]);

        $workOrder->recalculateTotals();
        $workOrder->refresh();

        // Cash payment 250, Refund 50 (net cash = 200), Bad debt 100 => total_paid = 300, balance = 200
        Payment::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'work_order_id' => $workOrder->id,
            'type' => PaymentType::PAYMENT,
            'payment_method' => 'cash',
            'amount' => 250.00,
            'payment_date' => now()->toDateString(),
            'created_by' => $user->id,
        ]);

        Payment::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'work_order_id' => $workOrder->id,
            'type' => PaymentType::REFUND,
            'payment_method' => 'cash',
            'amount' => 50.00,
            'payment_date' => now()->toDateString(),
            'created_by' => $user->id,
        ]);

        Payment::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'work_order_id' => $workOrder->id,
            'type' => PaymentType::BAD_DEBT,
            'payment_method' => 'bad_debt',
            'amount' => 100.00,
            'payment_date' => now()->toDateString(),
            'created_by' => $user->id,
        ]);

        $workOrder->refresh();

        $this->assertEquals(300.00, $workOrder->total_paid);
        $this->assertEquals(100.00, $workOrder->bad_debt);
        $this->assertEquals(200.00, $workOrder->net_cash_paid);
        $this->assertEquals(200.00, $workOrder->balance);
        $this->assertFalse($workOrder->isFullyPaid());

        // Verify outstandingBalanceSql() agrees with balance > 0 (200.00 remaining)
        $this->assertTrue(
            WorkOrder::whereKey($workOrder->id)
                ->whereRaw(WorkOrder::outstandingBalanceSql())
                ->exists()
        );

        // Verify printPayments() controller prop values
        $response = $this->get(route('work-orders.print.payments', $workOrder));
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('WorkOrders/Print/Payments')
            ->where('grandTotal', 500)
            ->where('totalPaid', 300)
            ->where('badDebt', 100)
            ->where('balance', 200)
        );

        // Pay remaining 200 -> total_paid = 500 (including 100 bad_debt), balance = 0, isFullyPaid = true
        Payment::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'work_order_id' => $workOrder->id,
            'type' => PaymentType::PAYMENT,
            'payment_method' => 'card',
            'amount' => 200.00,
            'payment_date' => now()->toDateString(),
            'created_by' => $user->id,
        ]);

        $workOrder->refresh();
        $this->assertEquals(500.00, $workOrder->total_paid);
        $this->assertEquals(0.00, $workOrder->balance);
        $this->assertTrue($workOrder->isFullyPaid());

        $this->assertFalse(
            WorkOrder::whereKey($workOrder->id)
                ->whereRaw(WorkOrder::outstandingBalanceSql())
                ->exists()
        );
    }
}
