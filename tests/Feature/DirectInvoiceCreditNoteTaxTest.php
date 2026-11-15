<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\InvoiceType;
use App\Models\Center;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Part;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\CreditNoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DirectInvoiceCreditNoteTaxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::findOrCreate('crm.invoices.view', 'web');
        Permission::findOrCreate('crm.invoices.create', 'web');
        Permission::findOrCreate('crm.invoices.update', 'web');
    }

    /**
     * @return array{tenant: Tenant, center: Center, user: User, customer: Customer, warehouse: Warehouse}
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
            'crm.invoices.view',
            'crm.invoices.create',
            'crm.invoices.update',
        ]);

        $customer = Customer::factory()->create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
        ]);

        $warehouse = Warehouse::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'name' => 'Main Warehouse',
            'code' => 'WH-MAIN-'.$tenant->id,
            'is_active' => true,
            'is_default' => true,
        ]);

        return compact('tenant', 'center', 'user', 'customer', 'warehouse');
    }

    private function createDirectInvoice(array $ctx, array $overrides = []): Invoice
    {
        return Invoice::create(array_merge([
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'customer_id' => $ctx['customer']->id,
            'work_order_id' => null,
            'invoice_number' => 'INV-DIR-'.uniqid(),
            'issue_date' => now(),
            'supply_date' => now()->toDateString(),
            'type' => InvoiceType::INVOICE,
            'status' => 'valid',
            'payment_status' => 'unpaid',
            'tax_enabled_snapshot' => true,
            'pricing_mode_snapshot' => 'exclusive',
            'tax_rate_snapshot' => 15.00,
            'total_excl_tax' => 0.00,
            'total_tax' => 0.00,
            'total_incl_tax' => 0.00,
        ], $overrides));
    }

    public function test_direct_invoice_with_15_percent_taxable_line_refunds_correctly(): void
    {
        $ctx = $this->createContext();
        $this->actingAs($ctx['user']);

        $invoice = $this->createDirectInvoice($ctx, [
            'total_excl_tax' => 100.00,
            'total_tax' => 15.00,
            'total_incl_tax' => 115.00,
        ]);

        $line = InvoiceLine::create([
            'invoice_id' => $invoice->id,
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'is_part' => false,
            'description' => 'Taxable Consultation',
            'qty' => 1,
            'unit_price' => 100.00,
            'is_taxable' => true,
            'tax_category_code' => 'S',
            'tax_rate_snapshot' => 15.00,
            'tax_amount' => 15.00,
            'line_total_excl_tax' => 100.00,
            'line_total_incl_tax' => 115.00,
        ]);

        $service = app(CreditNoteService::class);
        $cn = $service->createCreditNoteForInvoice($invoice, [
            'services' => [['id' => $line->id, 'return_amount' => 100.00]],
        ], $ctx['user']);

        $this->assertEquals(100.00, (float) $cn->total_excl_tax);
        $this->assertEquals(15.00, (float) $cn->total_tax);
        $this->assertEquals(115.00, (float) $cn->total_incl_tax);

        $cnLine = $cn->lines()->first();
        $this->assertTrue($cnLine->is_taxable);
        $this->assertEquals('S', $cnLine->tax_category_code);
        $this->assertEquals(15.00, (float) $cnLine->tax_rate_snapshot);
        $this->assertEquals(15.00, (float) $cnLine->tax_amount);
    }

    public function test_direct_invoice_with_exempt_line_remains_exempt_even_when_invoice_tax_enabled(): void
    {
        $ctx = $this->createContext();
        $this->actingAs($ctx['user']);

        $part = Part::create([
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'name_ar' => 'قطعة معفاة',
            'name_en' => 'Exempt Part',
            'sku' => 'EXM-1',
            'cost_price' => 50,
            'selling_price' => 100,
            'is_active' => true,
        ]);

        $invoice = $this->createDirectInvoice($ctx, [
            'tax_enabled_snapshot' => true,
            'tax_rate_snapshot' => 15.00,
            'total_excl_tax' => 300.00,
            'total_tax' => 0.00,
            'total_incl_tax' => 300.00,
        ]);

        $svcLine = InvoiceLine::create([
            'invoice_id' => $invoice->id,
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'is_part' => false,
            'description' => 'Exempt Service',
            'qty' => 1,
            'unit_price' => 100.00,
            'is_taxable' => false,
            'tax_category_code' => 'E',
            'tax_rate_snapshot' => 0.00,
            'tax_amount' => 0.00,
            'line_total_excl_tax' => 100.00,
            'line_total_incl_tax' => 100.00,
        ]);

        $partLine = InvoiceLine::create([
            'invoice_id' => $invoice->id,
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'is_part' => true,
            'part_id' => $part->id,
            'description' => 'Exempt Part',
            'qty' => 2,
            'unit_price' => 100.00,
            'is_taxable' => false,
            'tax_category_code' => 'E',
            'tax_rate_snapshot' => 0.00,
            'tax_amount' => 0.00,
            'line_total_excl_tax' => 200.00,
            'line_total_incl_tax' => 200.00,
        ]);

        $service = app(CreditNoteService::class);
        $summary = $service->getReturnableSummaryForInvoice($invoice);

        $this->assertEquals(0.0, $summary['services'][0]['tax_rate']);
        $this->assertFalse($summary['services'][0]['is_taxable']);
        $this->assertEquals(0.0, $summary['parts'][0]['tax_rate']);
        $this->assertFalse($summary['parts'][0]['is_taxable']);

        $cn = $service->createCreditNoteForInvoice($invoice, [
            'services' => [['id' => $svcLine->id, 'return_amount' => 100.00]],
            'parts' => [['id' => $partLine->id, 'return_qty' => 1, 'return_to_stock' => false]],
        ], $ctx['user']);

        $this->assertEquals(200.00, (float) $cn->total_excl_tax);
        $this->assertEquals(0.00, (float) $cn->total_tax);
        $this->assertEquals(200.00, (float) $cn->total_incl_tax);

        foreach ($cn->lines as $cnLine) {
            $this->assertFalse($cnLine->is_taxable);
            $this->assertEquals('E', $cnLine->tax_category_code);
            $this->assertEquals(0.00, (float) $cnLine->tax_rate_snapshot);
            $this->assertEquals(0.00, (float) $cnLine->tax_amount);
        }
    }

    public function test_direct_invoice_with_mixed_taxable_and_exempt_lines(): void
    {
        $ctx = $this->createContext();
        $this->actingAs($ctx['user']);

        $invoice = $this->createDirectInvoice($ctx, [
            'total_excl_tax' => 300.00,
            'total_tax' => 15.00,
            'total_incl_tax' => 315.00,
        ]);

        $taxableLine = InvoiceLine::create([
            'invoice_id' => $invoice->id,
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'is_part' => false,
            'description' => 'Taxable Inspection',
            'qty' => 1,
            'unit_price' => 100.00,
            'is_taxable' => true,
            'tax_category_code' => 'S',
            'tax_rate_snapshot' => 15.00,
            'tax_amount' => 15.00,
            'line_total_excl_tax' => 100.00,
            'line_total_incl_tax' => 115.00,
        ]);

        $exemptLine = InvoiceLine::create([
            'invoice_id' => $invoice->id,
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'is_part' => false,
            'description' => 'Government Fee (Exempt)',
            'qty' => 1,
            'unit_price' => 200.00,
            'is_taxable' => false,
            'tax_category_code' => 'E',
            'tax_rate_snapshot' => 0.00,
            'tax_amount' => 0.00,
            'line_total_excl_tax' => 200.00,
            'line_total_incl_tax' => 200.00,
        ]);

        $service = app(CreditNoteService::class);
        $cn = $service->createCreditNoteForInvoice($invoice, [
            'services' => [
                ['id' => $taxableLine->id, 'return_amount' => 100.00],
                ['id' => $exemptLine->id, 'return_amount' => 200.00],
            ],
        ], $ctx['user']);

        $this->assertEquals(300.00, (float) $cn->total_excl_tax);
        $this->assertEquals(15.00, (float) $cn->total_tax);
        $this->assertEquals(315.00, (float) $cn->total_incl_tax);
        $this->assertEquals(100.00, (float) $cn->total_taxable_amount);
    }

    public function test_direct_invoice_with_different_per_line_tax_rates(): void
    {
        $ctx = $this->createContext();
        $this->actingAs($ctx['user']);

        $invoice = $this->createDirectInvoice($ctx, [
            'tax_rate_snapshot' => 15.00,
            'total_excl_tax' => 200.00,
            'total_tax' => 20.00,
            'total_incl_tax' => 220.00,
        ]);

        $line15 = InvoiceLine::create([
            'invoice_id' => $invoice->id,
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'is_part' => false,
            'description' => 'Service 15%',
            'qty' => 1,
            'unit_price' => 100.00,
            'is_taxable' => true,
            'tax_category_code' => 'S',
            'tax_rate_snapshot' => 15.00,
            'tax_amount' => 15.00,
            'line_total_excl_tax' => 100.00,
            'line_total_incl_tax' => 115.00,
        ]);

        $line5 = InvoiceLine::create([
            'invoice_id' => $invoice->id,
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'is_part' => false,
            'description' => 'Service 5%',
            'qty' => 1,
            'unit_price' => 100.00,
            'is_taxable' => true,
            'tax_category_code' => 'S',
            'tax_rate_snapshot' => 5.00,
            'tax_amount' => 5.00,
            'line_total_excl_tax' => 100.00,
            'line_total_incl_tax' => 105.00,
        ]);

        $service = app(CreditNoteService::class);
        $summary = $service->getReturnableSummaryForInvoice($invoice);

        $this->assertEquals(15.0, $summary['services'][0]['tax_rate']);
        $this->assertEquals(5.0, $summary['services'][1]['tax_rate']);

        $cn = $service->createCreditNoteForInvoice($invoice, [
            'services' => [
                ['id' => $line15->id, 'return_amount' => 50.00],
                ['id' => $line5->id, 'return_amount' => 100.00],
            ],
        ], $ctx['user']);

        // 50 * 15% = 7.50, 100 * 5% = 5.00 => excl 150.00, tax 12.50, incl 162.50
        $this->assertEquals(150.00, (float) $cn->total_excl_tax);
        $this->assertEquals(12.50, (float) $cn->total_tax);
        $this->assertEquals(162.50, (float) $cn->total_incl_tax);
    }

    public function test_partial_full_multiple_and_cancelled_credit_notes_on_direct_invoice(): void
    {
        $ctx = $this->createContext();
        $this->actingAs($ctx['user']);

        $part = Part::create([
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'name_ar' => 'فلتر',
            'name_en' => 'Filter',
            'sku' => 'FLT-DIR-1',
            'cost_price' => 40,
            'selling_price' => 100,
            'is_active' => true,
        ]);

        $invoice = $this->createDirectInvoice($ctx, [
            'total_excl_tax' => 400.00,
            'total_tax' => 45.00,
            'total_incl_tax' => 445.00,
        ]);

        $taxableSvc = InvoiceLine::create([
            'invoice_id' => $invoice->id,
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'is_part' => false,
            'description' => 'Service 15%',
            'qty' => 1,
            'unit_price' => 100.00,
            'is_taxable' => true,
            'tax_category_code' => 'S',
            'tax_rate_snapshot' => 15.00,
            'tax_amount' => 15.00,
            'line_total_excl_tax' => 100.00,
            'line_total_incl_tax' => 115.00,
        ]);

        $taxablePart = InvoiceLine::create([
            'invoice_id' => $invoice->id,
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'is_part' => true,
            'part_id' => $part->id,
            'description' => 'Filter',
            'qty' => 2,
            'unit_price' => 100.00,
            'is_taxable' => true,
            'tax_category_code' => 'S',
            'tax_rate_snapshot' => 15.00,
            'tax_amount' => 30.00,
            'line_total_excl_tax' => 200.00,
            'line_total_incl_tax' => 230.00,
        ]);

        $exemptSvc = InvoiceLine::create([
            'invoice_id' => $invoice->id,
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'is_part' => false,
            'description' => 'Exempt Fee',
            'qty' => 1,
            'unit_price' => 100.00,
            'is_taxable' => false,
            'tax_category_code' => 'E',
            'tax_rate_snapshot' => 0.00,
            'tax_amount' => 0.00,
            'line_total_excl_tax' => 100.00,
            'line_total_incl_tax' => 100.00,
        ]);

        $service = app(CreditNoteService::class);

        // 1. Partial credit note
        $cn1 = $service->createCreditNoteForInvoice($invoice, [
            'services' => [
                ['id' => $taxableSvc->id, 'return_amount' => 40.00],
                ['id' => $exemptSvc->id, 'return_amount' => 50.00],
            ],
            'parts' => [
                ['id' => $taxablePart->id, 'return_qty' => 1, 'return_to_stock' => false],
            ],
        ], $ctx['user']);

        // 40*15% = 6, 50*0% = 0, part 100*15% = 15 => excl 190, tax 21, incl 211
        $this->assertEquals(190.00, (float) $cn1->total_excl_tax);
        $this->assertEquals(21.00, (float) $cn1->total_tax);
        $this->assertEquals(211.00, (float) $cn1->total_incl_tax);

        // 2. Cancel first credit note -> restores full refundable amounts
        $cn1->fresh()->forceFill(['status' => 'cancelled'])->save();

        $summaryAfterCancel = $service->getReturnableSummaryForInvoice($invoice);
        $this->assertEquals(100.00, $summaryAfterCancel['services'][0]['refundable_amount']);
        $this->assertEquals(100.00, $summaryAfterCancel['services'][1]['refundable_amount']);
        $this->assertEquals(2.0, $summaryAfterCancel['parts'][0]['refundable_qty']);

        // 3. Two consecutive credit notes summing to full refund
        $cn2 = $service->createCreditNoteForInvoice($invoice, [
            'services' => [
                ['id' => $taxableSvc->id, 'return_amount' => 60.00],
            ],
            'parts' => [
                ['id' => $taxablePart->id, 'return_qty' => 1, 'return_to_stock' => false],
            ],
        ], $ctx['user']);

        $this->assertEquals(160.00, (float) $cn2->total_excl_tax);
        $this->assertEquals(24.00, (float) $cn2->total_tax);
        $this->assertEquals(184.00, (float) $cn2->total_incl_tax);

        $cn3 = $service->createCreditNoteForInvoice($invoice, [
            'services' => [
                ['id' => $taxableSvc->id, 'return_amount' => 40.00],
                ['id' => $exemptSvc->id, 'return_amount' => 100.00],
            ],
            'parts' => [
                ['id' => $taxablePart->id, 'return_qty' => 1, 'return_to_stock' => false],
            ],
        ], $ctx['user']);

        $this->assertEquals(240.00, (float) $cn3->total_excl_tax);
        $this->assertEquals(21.00, (float) $cn3->total_tax);
        $this->assertEquals(261.00, (float) $cn3->total_incl_tax);

        // 4. Attempting to exceed remaining refundable amount fails
        $this->expectException(InvalidArgumentException::class);
        $service->createCreditNoteForInvoice($invoice, [
            'services' => [
                ['id' => $taxableSvc->id, 'return_amount' => 1.00],
            ],
        ], $ctx['user']);
    }

    public function test_tenant_and_center_isolation_for_direct_invoice_credit_note_routes(): void
    {
        $ctx1 = $this->createContext();
        $ctx2 = $this->createContext();

        // Another center in same tenant
        $otherCenter = Center::factory()->create(['tenant_id' => $ctx1['tenant']->id]);
        $sameTenantOtherCenterUser = User::factory()->create([
            'tenant_id' => $ctx1['tenant']->id,
            'current_center_id' => $otherCenter->id,
            'two_factor_confirmed_at' => now(),
        ]);
        $sameTenantOtherCenterUser->centers()->attach($otherCenter->id, ['tenant_id' => $ctx1['tenant']->id]);
        app(PermissionRegistrar::class)->setPermissionsTeamId($ctx1['tenant']->id);
        $sameTenantOtherCenterUser->givePermissionTo(['crm.invoices.view', 'crm.invoices.create']);

        $invoice = $this->createDirectInvoice($ctx1, [
            'total_excl_tax' => 100.00,
            'total_tax' => 15.00,
            'total_incl_tax' => 115.00,
        ]);

        $line = InvoiceLine::create([
            'invoice_id' => $invoice->id,
            'tenant_id' => $ctx1['tenant']->id,
            'center_id' => $ctx1['center']->id,
            'is_part' => false,
            'description' => 'Service',
            'qty' => 1,
            'unit_price' => 100.00,
            'is_taxable' => true,
            'tax_category_code' => 'S',
            'tax_rate_snapshot' => 15.00,
            'tax_amount' => 15.00,
            'line_total_excl_tax' => 100.00,
            'line_total_incl_tax' => 115.00,
        ]);

        // Different tenant user cannot access summary or store
        $this->actingAs($ctx2['user'])
            ->getJson(route('app.invoices.credit-note.summary', $invoice->id))
            ->assertNotFound();

        $this->actingAs($ctx2['user'])
            ->postJson(route('app.invoices.credit-note.store', $invoice->id), [
                'services' => [['id' => $line->id, 'return_amount' => 100.00]],
            ])
            ->assertNotFound();

        // Same tenant, different center user cannot access summary or store
        $this->actingAs($sameTenantOtherCenterUser)
            ->getJson(route('app.invoices.credit-note.summary', $invoice->id))
            ->assertForbidden();

        $this->actingAs($sameTenantOtherCenterUser)
            ->postJson(route('app.invoices.credit-note.store', $invoice->id), [
                'services' => [['id' => $line->id, 'return_amount' => 100.00]],
            ])
            ->assertForbidden();
    }
}
