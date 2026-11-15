<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\InvoiceType;
use App\Models\Center;
use App\Models\CenterSequence;
use App\Models\Customer;
use App\Models\InventoryBalance;
use App\Models\InventoryMove;
use App\Models\InventoryUnit;
use App\Models\Invoice;
use App\Models\Part;
use App\Models\Tenant;
use App\Models\TenantTaxSetting;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\PaymentService;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class R1cDirectSalesAndTaxParityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach ([
            Permissions::INVOICES_VIEW,
            Permissions::INVOICES_CREATE,
            Permissions::INVOICES_EXTRA_DISCOUNT,
            Permissions::PAYMENTS_CREATE,
            Permissions::INVENTORY_VIEW,
        ] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
    }

    protected function createContext(bool $vatEnabled = true, bool $partsInclusive = false, float $vatRate = 15.0): array
    {
        $tenant = Tenant::factory()->create(['country' => 'SA']);
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);

        TenantTaxSetting::create([
            'tenant_id' => $tenant->id,
            'vat_enabled' => $vatEnabled,
            'vat_rate' => $vatRate,
            'parts_vat_rate' => $vatRate,
            'parts_inclusive' => $partsInclusive,
            'services_inclusive' => false,
            'currency_code' => 'SAR',
            'tax_number' => '300000000000003',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
        ]);
        $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        $user->givePermissionTo([
            Permissions::INVOICES_VIEW,
            Permissions::INVOICES_CREATE,
            Permissions::PAYMENTS_CREATE,
            Permissions::INVENTORY_VIEW,
        ]);

        $warehouse = Warehouse::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'name' => 'Main Warehouse',
            'is_default' => true,
            'is_active' => true,
        ]);

        $unit = InventoryUnit::create([
            'tenant_id' => $tenant->id,
            'name_ar' => 'قطعة',
            'name_en' => 'Piece',
            'is_active' => true,
        ]);

        $part = Part::create([
            'tenant_id' => $tenant->id,
            'sku' => 'PART-R1C-'.random_int(100, 999),
            'name_ar' => 'قطعة غيار أصلية',
            'name_en' => 'OEM Part',
            'unit_id' => $unit->id,
            'is_active' => true,
            'default_sale_price' => 150.00,
            'min_sale_price' => 120.00,
            'cost_price' => 80.00,
        ]);

        InventoryBalance::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'warehouse_id' => $warehouse->id,
            'part_id' => $part->id,
            'qty_on_hand' => 20.00,
            'wac_cost' => 80.00,
        ]);

        $customer = Customer::factory()->create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
        ]);

        $user->refresh();

        return [$tenant, $center, $user, $warehouse, $part, $customer];
    }

    public function test_forged_totals_from_browser_are_ignored_and_computed_server_side_with_15_percent_tax(): void
    {
        [$tenant, $center, $user, $warehouse, $part, $customer] = $this->createContext(true, false, 15.0);
        $this->actingAs($user);

        // Qty 3 * 150 = 450 excl tax, 15% tax = 67.50, incl = 517.50
        // Client sends forged 1.00 totals and 0.00 tax!
        $response = $this->post(route('app.purchasing.sales-invoices.store'), [
            'customer_id' => $customer->id,
            'issue_date' => now()->format('Y-m-d'),
            'items' => [
                [
                    'part_id' => $part->id,
                    'warehouse_id' => $warehouse->id,
                    'source' => 'warehouse',
                    'name' => $part->name_ar,
                    'qty' => 3,
                    'unit_price' => 150,
                    'discount' => 0,
                    'tax_amount' => 0,
                    'total' => 1,
                    'grand_total' => 1,
                ],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $invoice = Invoice::latest('id')->firstOrFail();
        $this->assertEquals(450.00, (float) $invoice->total_excl_tax);
        $this->assertEquals(67.50, (float) $invoice->total_tax);
        $this->assertEquals(517.50, (float) $invoice->total_incl_tax);
        $this->assertNotEmpty($invoice->seller_name_snapshot);
        $this->assertNotEmpty($invoice->zatca_qr_tlv);
        $this->assertStringStartsWith('INV-', $invoice->invoice_number);
        $this->assertEquals(1, CenterSequence::where('tenant_id', $tenant->id)->where('center_id', $center->id)->where('type', 'invoice')->value('current_value'));
    }

    public function test_unauthorized_price_below_minimum_is_rejected(): void
    {
        [$tenant, $center, $user, $warehouse, $part, $customer] = $this->createContext(true, false, 15.0);
        $this->actingAs($user);

        $response = $this->post(route('app.purchasing.sales-invoices.store'), [
            'customer_id' => $customer->id,
            'issue_date' => now()->format('Y-m-d'),
            'items' => [
                [
                    'part_id' => $part->id,
                    'warehouse_id' => $warehouse->id,
                    'source' => 'warehouse',
                    'name' => $part->name_ar,
                    'qty' => 2,
                    'unit_price' => 50, // below min_sale_price 120
                    'discount' => 0,
                ],
            ],
        ]);

        $response->assertSessionHasErrors('items.0.unit_price');
        $this->assertEquals(0, Invoice::count());
    }

    public function test_tax_disabled_and_inclusive_pricing_and_discounts_and_qty_gt_1(): void
    {
        // Case 1: Tax disabled (unregistered center) + percentage discount 10% on qty 5
        [$tenant1, $center1, $user1, $wh1, $part1, $cust1] = $this->createContext(false, false, 0.0);
        $this->actingAs($user1);

        // 5 * 150 = 750, 10% discount = 75 => net = 675 (unit net = 135 >= min 120), tax = 0
        $this->post(route('app.purchasing.sales-invoices.store'), [
            'customer_id' => $cust1->id,
            'issue_date' => now()->format('Y-m-d'),
            'items' => [
                [
                    'part_id' => $part1->id,
                    'warehouse_id' => $wh1->id,
                    'source' => 'warehouse',
                    'name' => $part1->name_ar,
                    'qty' => 5,
                    'unit_price' => 150,
                    'discount_type' => 'percentage',
                    'discount_value' => 10,
                ],
            ],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $invNoTax = Invoice::withoutGlobalScopes()->where('tenant_id', $tenant1->id)->firstOrFail();
        $this->assertFalse((bool) $invNoTax->tax_enabled_snapshot);
        $this->assertEquals(675.00, (float) $invNoTax->total_excl_tax);
        $this->assertEquals(0.00, (float) $invNoTax->total_tax);
        $this->assertEquals(675.00, (float) $invNoTax->total_incl_tax);

        // Case 2: Tax inclusive 15% + fixed discount 10/unit on qty 2
        [$tenant2, $center2, $user2, $wh2, $part2, $cust2] = $this->createContext(true, true, 15.0);
        $this->actingAs($user2);

        // 2 * 150 = 300, fixed discount 10/unit = 20 => net incl tax = 280 (unit net = 140 >= min 120)
        // excl = round(280 / 1.15, 2) = 243.48, tax = 36.52
        $this->post(route('app.purchasing.sales-invoices.store'), [
            'customer_id' => $cust2->id,
            'issue_date' => now()->format('Y-m-d'),
            'items' => [
                [
                    'part_id' => $part2->id,
                    'warehouse_id' => $wh2->id,
                    'source' => 'warehouse',
                    'name' => $part2->name_ar,
                    'qty' => 2,
                    'unit_price' => 150,
                    'discount_type' => 'fixed',
                    'discount_value' => 10,
                ],
            ],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $invIncl = Invoice::withoutGlobalScopes()->where('tenant_id', $tenant2->id)->firstOrFail();
        $this->assertTrue((bool) $invIncl->tax_enabled_snapshot);
        $this->assertEquals('inclusive', $invIncl->pricing_mode_snapshot);
        $this->assertEquals(280.00, (float) $invIncl->total_incl_tax);
        $this->assertEquals(243.48, (float) $invIncl->total_excl_tax);
        $this->assertEquals(36.52, (float) $invIncl->total_tax);
    }

    public function test_overpayment_is_rejected_and_multiple_partial_payments_and_returns_work(): void
    {
        [$tenant, $center, $user, $warehouse, $part, $customer] = $this->createContext(true, false, 15.0);
        $this->actingAs($user);

        // Overpayment attempt (total_incl_tax = 517.50, payment = 600.00) must fail and rollback stock
        $this->post(route('app.purchasing.sales-invoices.store'), [
            'customer_id' => $customer->id,
            'issue_date' => now()->format('Y-m-d'),
            'items' => [
                [
                    'part_id' => $part->id,
                    'warehouse_id' => $warehouse->id,
                    'source' => 'warehouse',
                    'name' => $part->name_ar,
                    'qty' => 3,
                    'unit_price' => 150,
                ],
            ],
            'payments' => [
                [
                    'amount' => 600.00,
                    'payment_method' => 'cash',
                    'payment_date' => now()->format('Y-m-d'),
                ],
            ],
        ])->assertSessionHasErrors('payments');

        $this->assertEquals(0, Invoice::count());
        $this->assertEquals(20.00, (float) InventoryBalance::where('part_id', $part->id)->value('qty_on_hand'));

        // Valid sale with 2 partial payments (100 + 200 = 300 of 517.50)
        $this->post(route('app.purchasing.sales-invoices.store'), [
            'customer_id' => $customer->id,
            'issue_date' => now()->format('Y-m-d'),
            'items' => [
                [
                    'part_id' => $part->id,
                    'warehouse_id' => $warehouse->id,
                    'source' => 'warehouse',
                    'name' => $part->name_ar,
                    'qty' => 3,
                    'unit_price' => 150,
                ],
            ],
            'payments' => [
                [
                    'amount' => 100.00,
                    'payment_method' => 'cash',
                    'payment_date' => now()->format('Y-m-d'),
                ],
                [
                    'amount' => 200.00,
                    'payment_method' => 'card',
                    'payment_date' => now()->format('Y-m-d'),
                ],
            ],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $invoice = Invoice::latest('id')->firstOrFail();
        $this->assertEquals('partial', $invoice->payment_status);
        $this->assertEquals(300.00, (float) $invoice->total_paid);
        $this->assertEquals(217.50, (float) $invoice->balance);
        $this->assertEquals(17.00, (float) InventoryBalance::where('part_id', $part->id)->value('qty_on_hand'));

        // Return 1 piece via Credit Note -> restores stock 17 -> 18 and offsets remaining balance
        $line = $invoice->lines()->firstOrFail();
        $this->postJson(route('app.invoices.credit-note.store', $invoice->id), [
            'parts' => [['id' => $line->id, 'return_qty' => 1, 'restock' => true]],
            'services' => [],
            'record_refund' => false,
        ])->assertOk();

        $invoice->refresh();
        $creditNote = Invoice::where('original_invoice_id', $invoice->id)
            ->where('type', InvoiceType::CREDIT_NOTE)
            ->firstOrFail();

        $this->assertEquals(172.50, (float) $creditNote->total_incl_tax);
        $this->assertEquals(45.00, (float) $invoice->balance); // 517.50 - 172.50 - 300.00
        $this->assertEquals(18.00, (float) InventoryBalance::where('part_id', $part->id)->value('qty_on_hand'));
        $this->assertEquals(1, InventoryMove::where('move_type', InventoryMove::TYPE_SALE)->count());
        $this->assertEquals(2, InventoryMove::count());

        // Pay final 45.00 via PaymentService
        app(PaymentService::class)->recordPayment($invoice, [
            'amount' => 45.00,
            'payment_method' => 'cash',
            'payment_date' => now()->format('Y-m-d'),
        ]);
        $invoice->refresh();
        $this->assertEquals(0.00, (float) $invoice->balance);
        $this->assertEquals('paid', $invoice->payment_status);
    }

    public function test_penny_rounding_reconciliation_and_historical_invoice_immutability_on_tax_change(): void
    {
        [$tenant, $center, $user, $warehouse, $part, $customer] = $this->createContext(true, false, 15.0);
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        $user->givePermissionTo(Permissions::INVOICES_EXTRA_DISCOUNT);
        $user->refresh();
        $this->actingAs($user);

        // 3 external lines of 10.03 each:
        // Line tax raw = 10.03 * 0.15 = 1.5045 -> rounded = 1.50 each (sum = 4.50)
        // Document base = 30.09 * 0.15 = 4.5135 -> rounded = 4.51 -> 0.01 penny reconciled on line 1!
        $this->post(route('app.purchasing.sales-invoices.store'), [
            'customer_id' => $customer->id,
            'issue_date' => now()->format('Y-m-d'),
            'items' => [
                ['source' => 'external', 'name' => 'Item A', 'qty' => 1, 'unit_price' => 10.03],
                ['source' => 'external', 'name' => 'Item B', 'qty' => 1, 'unit_price' => 10.03],
                ['source' => 'external', 'name' => 'Item C', 'qty' => 1, 'unit_price' => 10.03],
            ],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $invoice = Invoice::latest('id')->firstOrFail();
        $this->assertEquals(30.09, (float) $invoice->total_excl_tax);
        $this->assertEquals(4.51, (float) $invoice->total_tax);
        $this->assertEquals(34.60, (float) $invoice->total_incl_tax);

        // Change tenant tax settings AFTER issuance: historical invoice must remain untouched
        TenantTaxSetting::where('tenant_id', $tenant->id)->update([
            'vat_enabled' => false,
            'vat_rate' => 0,
            'parts_vat_rate' => 0,
        ]);

        $invoice->refresh();
        $this->assertTrue((bool) $invoice->tax_enabled_snapshot);
        $this->assertEquals(15.00, (float) $invoice->tax_rate_snapshot);
        $this->assertEquals(4.51, (float) $invoice->total_tax);
        $this->assertEquals(34.60, (float) $invoice->total_incl_tax);
    }

    public function test_tenant_and_center_isolation_blocks_cross_tenant_customer_or_part(): void
    {
        [$tenantA, $centerA, $userA, $whA, $partA, $custA] = $this->createContext();
        [$tenantB, $centerB, $userB, $whB, $partB, $custB] = $this->createContext();

        $this->actingAs($userA);

        // Attempting to use Tenant B's customer or part must return 404 (not found in Tenant A scope)
        $this->post(route('app.purchasing.sales-invoices.store'), [
            'customer_id' => $custB->id,
            'issue_date' => now()->format('Y-m-d'),
            'items' => [
                [
                    'part_id' => $partA->id,
                    'warehouse_id' => $whA->id,
                    'source' => 'warehouse',
                    'name' => $partA->name_ar,
                    'qty' => 1,
                    'unit_price' => 150,
                ],
            ],
        ])->assertNotFound();

        $this->assertEquals(0, Invoice::withoutGlobalScopes()->count());
    }
}
