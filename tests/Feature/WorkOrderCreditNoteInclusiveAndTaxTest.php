<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\InvoiceType;
use App\Models\Center;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Services\CreditNoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class WorkOrderCreditNoteInclusiveAndTaxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::findOrCreate('crm.work_orders.view', 'web');
        Permission::findOrCreate('crm.work_orders.update', 'web');
        Permission::findOrCreate('crm.invoices.view', 'web');
        Permission::findOrCreate('crm.invoices.create', 'web');
    }

    /**
     * @return array{tenant: Tenant, center: Center, user: User, customer: Customer, vehicle: Vehicle}
     */
    private function createContext(): array
    {
        $tenant = Tenant::factory()->create(['status' => 'active']);
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
            'two_factor_confirmed_at' => now(),
        ]);
        $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        $user->givePermissionTo([
            'crm.work_orders.view',
            'crm.work_orders.update',
            'crm.invoices.view',
            'crm.invoices.create',
        ]);

        $customer = Customer::factory()->create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
        ]);
        $vehicle = Vehicle::factory()->create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
        ]);

        return compact('tenant', 'center', 'user', 'customer', 'vehicle');
    }

    public function test_inclusive_service_with_stored_tax_columns_refunds_full_115_when_returning_100_excl(): void
    {
        $ctx = $this->createContext();
        $this->actingAs($ctx['user']);

        $wo = WorkOrder::create([
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'customer_id' => $ctx['customer']->id,
            'vehicle_id' => $ctx['vehicle']->id,
            'code' => 'WO-INCL-1',
            'status' => WorkOrder::STATUS_DONE,
            'pricing_mode_snapshot' => 'inclusive',
            'tax_enabled_snapshot' => true,
            'tax_rate_snapshot' => 15.00,
        ]);

        $item = WorkOrderItem::create([
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'work_order_id' => $wo->id,
            'title' => 'Inclusive Service',
            'qty' => 1,
            'unit_price' => 115.00,
            'status' => WorkOrderItem::STATUS_COMPLETED,
            'is_taxable' => true,
            'tax_rate_snapshot' => 15.00,
            'tax_amount' => 15.00,
            'line_total_excl_tax' => 100.00,
            'line_total_incl_tax' => 115.00,
        ]);

        Invoice::create([
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'customer_id' => $ctx['customer']->id,
            'work_order_id' => $wo->id,
            'invoice_number' => 'INV-INCL-1',
            'issue_date' => now(),
            'supply_date' => now()->toDateString(),
            'type' => InvoiceType::INVOICE,
            'status' => 'valid',
            'payment_status' => 'unpaid',
            'tax_enabled_snapshot' => true,
            'pricing_mode_snapshot' => 'inclusive',
            'tax_rate_snapshot' => 15.00,
            'total_excl_tax' => 100.00,
            'total_tax' => 15.00,
            'total_incl_tax' => 115.00,
        ]);

        $service = app(CreditNoteService::class);
        $summary = $service->getReturnableSummary($wo);

        $this->assertEquals(100.00, $summary['services'][0]['refundable_amount']);
        $this->assertEquals(115.00, $summary['services'][0]['original_total']);

        $creditNote = $service->createCreditNote($wo, [
            'services' => [
                ['work_order_item_id' => $item->id, 'return_amount' => 100.00],
            ],
        ], $ctx['user']);

        $this->assertEquals(100.00, (float) $creditNote->total_excl_tax);
        $this->assertEquals(15.00, (float) $creditNote->total_tax);
        $this->assertEquals(115.00, (float) $creditNote->total_incl_tax);
    }

    public function test_inclusive_service_without_stored_tax_columns_derives_excl_tax_and_refunds_full_115(): void
    {
        $ctx = $this->createContext();
        $this->actingAs($ctx['user']);

        $wo = WorkOrder::create([
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'customer_id' => $ctx['customer']->id,
            'vehicle_id' => $ctx['vehicle']->id,
            'code' => 'WO-INCL-2',
            'status' => WorkOrder::STATUS_DONE,
            'pricing_mode_snapshot' => 'inclusive',
            'tax_enabled_snapshot' => true,
            'tax_rate_snapshot' => 15.00,
        ]);

        $item = WorkOrderItem::create([
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'work_order_id' => $wo->id,
            'title' => 'Inclusive Legacy Service',
            'qty' => 1,
            'unit_price' => 115.00,
            'status' => WorkOrderItem::STATUS_COMPLETED,
            'is_taxable' => true,
            'tax_rate_snapshot' => 15.00,
            'line_total_excl_tax' => 0.00,
            'line_total_incl_tax' => 0.00,
        ]);

        Invoice::create([
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'customer_id' => $ctx['customer']->id,
            'work_order_id' => $wo->id,
            'invoice_number' => 'INV-INCL-2',
            'issue_date' => now(),
            'supply_date' => now()->toDateString(),
            'type' => InvoiceType::INVOICE,
            'status' => 'valid',
            'payment_status' => 'unpaid',
            'tax_enabled_snapshot' => true,
            'pricing_mode_snapshot' => 'inclusive',
            'tax_rate_snapshot' => 15.00,
            'total_excl_tax' => 100.00,
            'total_tax' => 15.00,
            'total_incl_tax' => 115.00,
        ]);

        $service = app(CreditNoteService::class);
        $summary = $service->getReturnableSummary($wo);

        $this->assertEquals(100.00, $summary['services'][0]['refundable_amount']);
        $this->assertEquals(115.00, $summary['services'][0]['original_total']);

        $creditNote = $service->createCreditNote($wo, [
            'services' => [
                ['work_order_item_id' => $item->id, 'return_amount' => 100.00],
            ],
        ], $ctx['user']);

        $this->assertEquals(100.00, (float) $creditNote->total_excl_tax);
        $this->assertEquals(15.00, (float) $creditNote->total_tax);
        $this->assertEquals(115.00, (float) $creditNote->total_incl_tax);
    }

    public function test_inclusive_partial_and_multiple_credit_notes_cannot_exceed_original_amount(): void
    {
        $ctx = $this->createContext();
        $this->actingAs($ctx['user']);

        $wo = WorkOrder::create([
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'customer_id' => $ctx['customer']->id,
            'vehicle_id' => $ctx['vehicle']->id,
            'code' => 'WO-INCL-PARTIAL',
            'status' => WorkOrder::STATUS_DONE,
            'pricing_mode_snapshot' => 'inclusive',
            'tax_enabled_snapshot' => true,
            'tax_rate_snapshot' => 15.00,
        ]);

        $item = WorkOrderItem::create([
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'work_order_id' => $wo->id,
            'title' => 'Inclusive Partial Service',
            'qty' => 1,
            'unit_price' => 115.00,
            'status' => WorkOrderItem::STATUS_COMPLETED,
            'is_taxable' => true,
            'tax_rate_snapshot' => 15.00,
            'tax_amount' => 15.00,
            'line_total_excl_tax' => 100.00,
            'line_total_incl_tax' => 115.00,
        ]);

        Invoice::create([
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'customer_id' => $ctx['customer']->id,
            'work_order_id' => $wo->id,
            'invoice_number' => 'INV-INCL-PARTIAL',
            'issue_date' => now(),
            'supply_date' => now()->toDateString(),
            'type' => InvoiceType::INVOICE,
            'status' => 'valid',
            'payment_status' => 'unpaid',
            'tax_enabled_snapshot' => true,
            'pricing_mode_snapshot' => 'inclusive',
            'tax_rate_snapshot' => 15.00,
            'total_excl_tax' => 100.00,
            'total_tax' => 15.00,
            'total_incl_tax' => 115.00,
        ]);

        $service = app(CreditNoteService::class);

        // Cancelled credit note must NOT reduce refundable_amount
        $cancelledCn = $service->createCreditNote($wo, [
            'services' => [['work_order_item_id' => $item->id, 'return_amount' => 40.00]],
        ], $ctx['user']);
        DB::table('invoices')->where('id', $cancelledCn->id)->update(['status' => 'cancelled']);

        $cn1 = $service->createCreditNote($wo, [
            'services' => [['work_order_item_id' => $item->id, 'return_amount' => 40.00]],
        ], $ctx['user']);
        $this->assertEquals(40.00, (float) $cn1->total_excl_tax);
        $this->assertEquals(6.00, (float) $cn1->total_tax);
        $this->assertEquals(46.00, (float) $cn1->total_incl_tax);

        $cn2 = $service->createCreditNote($wo, [
            'services' => [['work_order_item_id' => $item->id, 'return_amount' => 60.00]],
        ], $ctx['user']);
        $this->assertEquals(60.00, (float) $cn2->total_excl_tax);
        $this->assertEquals(9.00, (float) $cn2->total_tax);
        $this->assertEquals(69.00, (float) $cn2->total_incl_tax);

        $this->expectException(InvalidArgumentException::class);
        $service->createCreditNote($wo, [
            'services' => [['work_order_item_id' => $item->id, 'return_amount' => 1.00]],
        ], $ctx['user']);
    }

    public function test_mixed_taxable_and_exempt_items_respect_per_item_taxability_and_rate(): void
    {
        $ctx = $this->createContext();
        $this->actingAs($ctx['user']);

        $wo = WorkOrder::create([
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'customer_id' => $ctx['customer']->id,
            'vehicle_id' => $ctx['vehicle']->id,
            'code' => 'WO-MIXED-1',
            'status' => WorkOrder::STATUS_DONE,
            'pricing_mode_snapshot' => 'exclusive',
            'tax_enabled_snapshot' => true,
            'tax_rate_snapshot' => 15.00,
        ]);

        $taxableItem = WorkOrderItem::create([
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'work_order_id' => $wo->id,
            'title' => 'Taxable Service',
            'qty' => 1,
            'unit_price' => 100.00,
            'status' => WorkOrderItem::STATUS_COMPLETED,
            'is_taxable' => true,
            'tax_rate_snapshot' => 15.00,
            'tax_amount' => 15.00,
            'line_total_excl_tax' => 100.00,
            'line_total_incl_tax' => 115.00,
        ]);

        $exemptItem = WorkOrderItem::create([
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'work_order_id' => $wo->id,
            'title' => 'Exempt Service',
            'qty' => 1,
            'unit_price' => 50.00,
            'status' => WorkOrderItem::STATUS_COMPLETED,
            'is_taxable' => false,
            'tax_rate_snapshot' => 0.00,
            'tax_amount' => 0.00,
            'line_total_excl_tax' => 50.00,
            'line_total_incl_tax' => 50.00,
        ]);

        Invoice::create([
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'customer_id' => $ctx['customer']->id,
            'work_order_id' => $wo->id,
            'invoice_number' => 'INV-MIXED-1',
            'issue_date' => now(),
            'supply_date' => now()->toDateString(),
            'type' => InvoiceType::INVOICE,
            'status' => 'valid',
            'payment_status' => 'unpaid',
            'tax_enabled_snapshot' => true,
            'pricing_mode_snapshot' => 'exclusive',
            'tax_rate_snapshot' => 15.00,
            'total_excl_tax' => 150.00,
            'total_tax' => 15.00,
            'total_incl_tax' => 165.00,
        ]);

        $service = app(CreditNoteService::class);
        $summary = $service->getReturnableSummary($wo);

        $taxableSummary = collect($summary['services'])->firstWhere('work_order_item_id', $taxableItem->id);
        $exemptSummary = collect($summary['services'])->firstWhere('work_order_item_id', $exemptItem->id);

        $this->assertEquals(15.0, $taxableSummary['tax_rate']);
        $this->assertEquals(0.0, $exemptSummary['tax_rate']);

        $creditNote = $service->createCreditNote($wo, [
            'services' => [
                ['work_order_item_id' => $taxableItem->id, 'return_amount' => 100.00],
                ['work_order_item_id' => $exemptItem->id, 'return_amount' => 50.00],
            ],
        ], $ctx['user']);

        $this->assertEquals(150.00, (float) $creditNote->total_excl_tax);
        $this->assertEquals(15.00, (float) $creditNote->total_tax);
        $this->assertEquals(165.00, (float) $creditNote->total_incl_tax);
    }
}
