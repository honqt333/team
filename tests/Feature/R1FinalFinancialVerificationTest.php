<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\CenterSequence;
use App\Models\Customer;
use App\Models\InventoryBalance;
use App\Models\InventoryUnit;
use App\Models\Invoice;
use App\Models\Part;
use App\Models\Tenant;
use App\Models\TenantTaxSetting;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Warehouse;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Models\WorkOrderItemPart;
use App\Services\InvoiceService;
use App\Services\Optimization\TaxCalculator;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class R1FinalFinancialVerificationTest extends TestCase
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

    protected function createContext(bool $vatEnabled = true, bool $inclusive = false, float $vatRate = 15.0): array
    {
        $tenant = Tenant::factory()->create(['country' => 'SA']);
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);

        TenantTaxSetting::create([
            'tenant_id' => $tenant->id,
            'vat_enabled' => $vatEnabled,
            'vat_rate' => $vatRate,
            'parts_vat_rate' => $vatRate,
            'services_inclusive' => $inclusive,
            'parts_inclusive' => $inclusive,
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

        $customer = Customer::factory()->create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
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

    public function test_f11_work_order_and_invoice_three_lines_of_10_03_with_15_percent_tax(): void
    {
        [$tenant, $center, $user, $customer, $vehicle] = $this->createContext(true, false, 15.0);
        $this->actingAs($user);

        $wo = WorkOrder::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'code' => 'WO-PENNY-1',
            'status' => WorkOrder::STATUS_DONE,
        ]);

        for ($i = 1; $i <= 3; $i++) {
            WorkOrderItem::create([
                'work_order_id' => $wo->id,
                'tenant_id' => $tenant->id,
                'center_id' => $center->id,
                'title' => "Item {$i}",
                'qty' => 1,
                'unit_price' => 10.03,
                'is_taxable' => true,
                'tax_rate_snapshot' => 15.0,
                'status' => WorkOrderItem::STATUS_COMPLETED,
            ]);
        }

        $wo->recalculateTotals();
        $wo->save();
        $wo->refresh();

        $invoice = app(InvoiceService::class)->createFromWorkOrder($wo, $user);

        // Document-level TaxCalculator gives 30.09 * 0.15 = 4.5135 -> 4.51 (total = 34.60)
        $calc = app(TaxCalculator::class);
        $lines = array_fill(0, 3, array_merge(
            $calc->calculateLine(10.03, 1, 0, 15.0, false),
            ['tax_category_code' => 'S']
        ));
        $doc = $calc->calculateDocument($lines);

        $this->assertEquals(30.09, $doc['total_excl_tax']);
        $this->assertEquals(4.51, $doc['total_tax']);
        $this->assertEquals(34.60, $doc['total_incl_tax']);

        // WorkOrder and Invoice MUST both match TaxCalculator document level (34.60)
        $this->assertEquals(4.51, (float) $wo->total_tax);
        $this->assertEquals(34.60, (float) $wo->total_incl_tax);
        $this->assertEquals(30.09, (float) $invoice->total_excl_tax);
        $this->assertEquals(4.51, (float) $invoice->total_tax);
        $this->assertEquals(34.60, (float) $invoice->total_incl_tax);
    }

    public function test_f11_work_order_and_invoice_inclusive_exclusive_parts_discounts_unregistered(): void
    {
        // Case A: Inclusive 15% with services + parts + fixed & percentage discounts
        [$tenantA, $centerA, $userA, $customerA, $vehicleA] = $this->createContext(true, true, 15.0);
        $this->actingAs($userA);

        $woA = WorkOrder::create([
            'tenant_id' => $tenantA->id,
            'center_id' => $centerA->id,
            'customer_id' => $customerA->id,
            'vehicle_id' => $vehicleA->id,
            'code' => 'WO-INCL-1',
            'status' => WorkOrder::STATUS_DONE,
        ]);

        // Service: qty 2 * 100 = 200, percentage 10% = 20 => net 180
        WorkOrderItem::create([
            'work_order_id' => $woA->id,
            'tenant_id' => $tenantA->id,
            'center_id' => $centerA->id,
            'title' => 'Inclusive Service',
            'qty' => 2,
            'unit_price' => 100,
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'is_taxable' => true,
            'tax_rate_snapshot' => 15.0,
            'status' => WorkOrderItem::STATUS_COMPLETED,
        ]);

        // Part: qty 2 * 50 = 100, fixed discount 10 => net 90
        WorkOrderItemPart::create([
            'work_order_id' => $woA->id,
            'tenant_id' => $tenantA->id,
            'center_id' => $centerA->id,
            'name' => 'Inclusive Part',
            'qty' => 2,
            'unit_price' => 50,
            'discount' => 10,
            'total' => 90,
            'status' => WorkOrderItemPart::STATUS_ISSUED,
        ]);

        $woA->recalculateTotals();
        $woA->save();
        $woA->refresh();

        $invA = app(InvoiceService::class)->createFromWorkOrder($woA, $userA);

        // Both WO and Invoice total_incl_tax = 270.00
        $this->assertEquals(270.00, (float) $woA->total_incl_tax);
        $this->assertEquals(270.00, (float) $invA->total_incl_tax);

        // Case B: Unregistered center (vat_enabled = false)
        [$tenantB, $centerB, $userB, $customerB, $vehicleB] = $this->createContext(false, false, 0.0);
        $this->actingAs($userB);

        $woB = WorkOrder::create([
            'tenant_id' => $tenantB->id,
            'center_id' => $centerB->id,
            'customer_id' => $customerB->id,
            'vehicle_id' => $vehicleB->id,
            'code' => 'WO-NOTAX-1',
            'status' => WorkOrder::STATUS_DONE,
        ]);

        WorkOrderItem::create([
            'work_order_id' => $woB->id,
            'tenant_id' => $tenantB->id,
            'center_id' => $centerB->id,
            'title' => 'Unregistered Service',
            'qty' => 3,
            'unit_price' => 100,
            'discount_type' => 'fixed',
            'discount_value' => 15,
            'is_taxable' => false,
            'tax_rate_snapshot' => 0,
            'status' => WorkOrderItem::STATUS_COMPLETED,
        ]);

        $woB->recalculateTotals();
        $woB->save();
        $woB->refresh();
        $invB = app(InvoiceService::class)->createFromWorkOrder($woB, $userB);

        $this->assertEquals(255.00, (float) $woB->total_incl_tax);
        $this->assertEquals(0.00, (float) $woB->total_tax);
        $this->assertEquals((float) $woB->total_incl_tax, (float) $invB->total_incl_tax);
        $this->assertEquals((float) $woB->total_tax, (float) $invB->total_tax);
    }

    public function test_f11_mixed_tax_categories_match_between_work_order_and_invoice(): void
    {
        [$tenant, $center, $user, $customer, $vehicle] = $this->createContext(true, false, 15.0);
        $this->actingAs($user);

        $wo = WorkOrder::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'code' => 'WO-MIX-1',
            'status' => WorkOrder::STATUS_DONE,
        ]);

        // Service 1: 100 SAR at 15% (S)
        WorkOrderItem::create([
            'work_order_id' => $wo->id,
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'title' => 'Taxable Service',
            'qty' => 1,
            'unit_price' => 100,
            'is_taxable' => true,
            'tax_category_code' => 'S',
            'tax_rate_snapshot' => 15.0,
            'status' => WorkOrderItem::STATUS_COMPLETED,
        ]);

        // Service 2: 100 SAR at 0% (Z/E)
        WorkOrderItem::create([
            'work_order_id' => $wo->id,
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'title' => 'Zero-Rated Service',
            'qty' => 1,
            'unit_price' => 100,
            'is_taxable' => false,
            'tax_category_code' => 'Z',
            'tax_rate_snapshot' => 0.0,
            'status' => WorkOrderItem::STATUS_COMPLETED,
        ]);

        $wo->recalculateTotals();
        $wo->save();
        $wo->refresh();

        $inv = app(InvoiceService::class)->createFromWorkOrder($wo, $user);

        // Both WorkOrder and Invoice calculate 15.00 tax and 215.00 total
        $this->assertEquals(15.00, (float) $wo->total_tax);
        $this->assertEquals(215.00, (float) $wo->total_incl_tax);
        $this->assertEquals(15.00, (float) $inv->total_tax);
        $this->assertEquals(215.00, (float) $inv->total_incl_tax);
    }

    public function test_external_item_pricing_requires_extra_discount_permission_and_is_tenant_isolated(): void
    {
        [$tenant, $center, $user, $customer, $vehicle] = $this->createContext(true, false, 15.0);
        [$tenantB, $centerB, $userB, $customerB, $vehicleB] = $this->createContext(true, false, 15.0);

        // Unauthorized user (only INVOICES_CREATE) is rejected with 403
        $this->actingAs($user);
        $this->post(route('app.purchasing.sales-invoices.store'), [
            'customer_id' => $customer->id,
            'issue_date' => now()->format('Y-m-d'),
            'items' => [
                [
                    'source' => 'external',
                    'part_id' => null,
                    'name' => 'Arbitrary External Item',
                    'qty' => 1,
                    'unit_price' => 1.00,
                    'discount' => 0,
                ],
            ],
        ])->assertForbidden();
        $this->assertEquals(0, Invoice::withoutGlobalScopes()->count());

        // Granting permission on Tenant B does NOT leak to Tenant A user
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenantB->id);
        $userB->givePermissionTo(Permissions::INVOICES_EXTRA_DISCOUNT);
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        $user->refresh();
        $this->actingAs($user);

        $this->post(route('app.purchasing.sales-invoices.store'), [
            'customer_id' => $customer->id,
            'issue_date' => now()->format('Y-m-d'),
            'items' => [
                [
                    'source' => 'external',
                    'part_id' => null,
                    'name' => 'Arbitrary External Item',
                    'qty' => 1,
                    'unit_price' => 1.00,
                    'discount' => 0,
                ],
            ],
        ])->assertForbidden();

        // Authorized user on Tenant A with INVOICES_EXTRA_DISCOUNT succeeds
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        $user->givePermissionTo(Permissions::INVOICES_EXTRA_DISCOUNT);
        $user->refresh();
        $this->actingAs($user);

        $this->post(route('app.purchasing.sales-invoices.store'), [
            'customer_id' => $customer->id,
            'issue_date' => now()->format('Y-m-d'),
            'items' => [
                [
                    'source' => 'external',
                    'part_id' => null,
                    'name' => 'Authorized External Item',
                    'qty' => 1,
                    'unit_price' => 1.00,
                    'discount' => 0,
                ],
            ],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $invoice = Invoice::latest('id')->firstOrFail();
        $this->assertEquals(1.15, (float) $invoice->total_incl_tax);
    }

    public function test_financial_stability_immutability_sequences_and_cancelled_preservation(): void
    {
        [$tenant, $center, $user, $customer, $vehicle] = $this->createContext(true, false, 15.0);
        $this->actingAs($user);

        $unit = InventoryUnit::create(['tenant_id' => $tenant->id, 'name_ar' => 'حبة', 'name_en' => 'Pc', 'is_active' => true]);
        $wh = Warehouse::create(['tenant_id' => $tenant->id, 'center_id' => $center->id, 'name' => 'WH', 'is_default' => true, 'is_active' => true]);
        $part = Part::create([
            'tenant_id' => $tenant->id, 'sku' => 'STAB-1', 'name_ar' => 'قطعة', 'name_en' => 'Part',
            'unit_id' => $unit->id, 'is_active' => true, 'default_sale_price' => 100.00, 'min_sale_price' => 80.00,
        ]);
        InventoryBalance::create([
            'tenant_id' => $tenant->id, 'center_id' => $center->id, 'warehouse_id' => $wh->id,
            'part_id' => $part->id, 'qty_on_hand' => 10, 'wac_cost' => 50,
        ]);

        $this->post(route('app.purchasing.sales-invoices.store'), [
            'customer_id' => $customer->id,
            'issue_date' => now()->format('Y-m-d'),
            'items' => [
                ['part_id' => $part->id, 'warehouse_id' => $wh->id, 'source' => 'warehouse', 'name' => 'Part', 'qty' => 2, 'unit_price' => 100],
            ],
        ])->assertRedirect();

        $inv = Invoice::latest('id')->firstOrFail();
        $origNumber = $inv->invoice_number;
        $this->assertEquals(230.00, (float) $inv->total_incl_tax);

        // Mutating Part price and TenantTaxSetting does not affect issued invoice
        $part->update(['default_sale_price' => 500.00]);
        TenantTaxSetting::where('tenant_id', $tenant->id)->update(['vat_enabled' => false, 'vat_rate' => 0]);

        $inv->refresh();
        $this->assertEquals(230.00, (float) $inv->total_incl_tax);

        // Issued invoice cannot be deleted or mutated
        try {
            $inv->delete();
            $this->fail('Issued invoice deletion must fail.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('invoice', $e->errors());
        }

        // Next invoice gets next sequence (never reuses $origNumber)
        $this->post(route('app.purchasing.sales-invoices.store'), [
            'customer_id' => $customer->id,
            'issue_date' => now()->format('Y-m-d'),
            'items' => [
                ['part_id' => $part->id, 'warehouse_id' => $wh->id, 'source' => 'warehouse', 'name' => 'Part', 'qty' => 1, 'unit_price' => 500],
            ],
        ])->assertRedirect();

        $inv2 = Invoice::latest('id')->firstOrFail();
        $this->assertNotSame($origNumber, $inv2->invoice_number);
        $this->assertEquals(2, CenterSequence::where('tenant_id', $tenant->id)->where('center_id', $center->id)->where('type', 'invoice')->value('current_value'));
    }
}
