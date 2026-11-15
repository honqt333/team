<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Tenant;
use App\Models\TenantTaxSetting;
use App\Models\User;
use App\Services\Print\PrintDocumentDataBuilder;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class R2b2UnifiedPrintDtoAndQrTest extends TestCase
{
    use RefreshDatabase;

    public function test_issued_invoice_preserves_frozen_seller_identity_and_totals_after_center_changes(): void
    {
        $tenant = Tenant::create([
            'name' => 'شركة الصيانة الأصلية',
            'slug' => 'orig-co-'.Str::lower(Str::random(5)),
            'status' => 'active',
            'country' => 'SA',
        ]);
        $taxSettings = TenantTaxSetting::create([
            'tenant_id' => $tenant->id,
            'vat_enabled' => true,
            'vat_rate' => 15,
            'services_inclusive' => false,
            'tax_number' => '310000000000001',
        ]);
        $center = Center::create([
            'tenant_id' => $tenant->id,
            'name' => 'Original Branch',
            'name_ar' => 'الفرع الأصلي',
            'name_en' => 'Original Branch',
            'slug' => 'orig-branch',
            'vat_number' => '310000000000001',
            'cr_number' => '1010111222',
            'is_active' => true,
        ]);
        Permission::findOrCreate(Permissions::INVOICES_VIEW, 'web');
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
        ]);
        $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);
        setPermissionsTeamId($tenant->id);
        $user->givePermissionTo(Permissions::INVOICES_VIEW);

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'name' => 'خالد الحربي',
            'phone' => '+966501234567',
        ]);

        $issued = Invoice::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-FROZEN-01',
            'type' => 'invoice',
            'subtype' => 'simplified',
            'status' => 'valid',
            'payment_status' => 'paid',
            'issue_date' => '2026-11-10',
            'supply_date' => '2026-11-10',
            'currency_code' => 'SAR',
            'tax_enabled_snapshot' => true,
            'tax_rate_snapshot' => 15,
            'pricing_mode_snapshot' => 'exclusive',
            'total_excl_tax' => 200.00,
            'total_tax' => 30.00,
            'total_incl_tax' => 230.00,
            'total_paid' => 230.00,
            'balance' => 0.00,
            'seller_name_snapshot' => 'الفرع الأصلي التاريخي',
            'seller_name_en_snapshot' => 'Historical Original Branch',
            'seller_vat_snapshot' => '310000000000001',
            'seller_cr_snapshot' => '1010111222',
            'customer_name_snapshot' => 'خالد الحربي',
            'zatca_qr_tlv' => 'FROZEN-TLV-PAYLOAD',
        ]);

        // Mutate center, tenant, and tax settings AFTER invoice issuance
        $center->update([
            'name' => 'Renamed Branch',
            'name_ar' => 'الفرع المعدل لاحقاً',
            'name_en' => 'Renamed Branch',
            'vat_number' => '399999999999999',
            'cr_number' => '9999888877',
        ]);
        $tenant->update(['name' => 'شركة معدلة لاحقاً']);
        $taxSettings->update(['tax_number' => '399999999999999', 'vat_rate' => 20]);

        $this->actingAs($user)
            ->withSession(['active_center_id' => $center->id])
            ->get(route('app.invoices.print', $issued))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Invoices/Print')
                ->where('printDocument.seller.name_ar', 'الفرع الأصلي التاريخي')
                ->where('printDocument.seller.name_en', 'Historical Original Branch')
                ->where('printDocument.seller.vat_number', '310000000000001')
                ->where('printDocument.seller.cr_number', '1010111222')
                ->where('printDocument.totals.subtotal_excl_tax', 200)
                ->where('printDocument.totals.total_tax', 30)
                ->where('printDocument.totals.grand_total_incl_tax', 230)
                ->where('printDocument.qr_tlv', 'FROZEN-TLV-PAYLOAD')
                ->where('printDocument.qr_mandatory', true)
                ->where('zatca_qr_svg', fn (string $val) => str_starts_with($val, 'data:image/svg+xml;base64,'))
            );
    }

    public function test_draft_invoice_uses_current_center_and_records_no_missing_issued_snapshots(): void
    {
        $tenant = Tenant::create([
            'name' => 'ورشة المسودة',
            'slug' => 'draft-co-'.Str::lower(Str::random(5)),
            'status' => 'active',
            'country' => 'SA',
            'cr_number' => '1010555666',
        ]);
        TenantTaxSetting::create([
            'tenant_id' => $tenant->id,
            'vat_enabled' => true,
            'vat_rate' => 15,
            'tax_number' => '310111222333444',
        ]);
        $center = Center::create([
            'tenant_id' => $tenant->id,
            'name' => 'Current Branch',
            'name_ar' => 'الفرع الحالي',
            'name_en' => 'Current Branch',
            'slug' => 'current-branch',
            'is_active' => true,
        ]);
        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'name' => 'عميل مسودة',
            'phone' => '+966501111111',
        ]);

        $draft = Invoice::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'DRAFT-001',
            'type' => 'invoice',
            'subtype' => 'simplified',
            'status' => 'draft',
            'payment_status' => 'unpaid',
            'issue_date' => '2026-11-15',
            'supply_date' => '2026-11-15',
            'currency_code' => 'SAR',
            'tax_enabled_snapshot' => false,
            'tax_rate_snapshot' => 0,
            'total_excl_tax' => 100,
            'total_tax' => 0,
            'total_incl_tax' => 100,
        ]);

        $dto = app(PrintDocumentDataBuilder::class)->buildFromInvoice($draft, 'ar');

        $this->assertSame('الفرع الحالي', $dto->seller['name_ar']);
        $this->assertSame('310111222333444', $dto->seller['vat_number']);
        $this->assertSame('1010555666', $dto->seller['cr_number']);
        $this->assertNull($dto->qrDataUri);
        $this->assertFalse($dto->qrMandatory);
        $this->assertSame([], $dto->missingSnapshots);
    }

    public function test_issued_invoice_with_missing_snapshots_reports_missing_fields_without_using_platform_name(): void
    {
        $tenant = Tenant::create([
            'name' => 'مركز النخبة',
            'slug' => 'missing-snap-'.Str::lower(Str::random(5)),
            'status' => 'active',
            'country' => 'SA',
        ]);
        $center = Center::create([
            'tenant_id' => $tenant->id,
            'name' => 'Elite Branch',
            'name_ar' => 'فرع النخبة',
            'slug' => 'elite-branch',
            'is_active' => true,
        ]);
        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'name' => 'عميل قديم',
            'phone' => '+966502222222',
        ]);

        $legacyIssued = Invoice::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-LEGACY-01',
            'type' => 'invoice',
            'subtype' => 'simplified',
            'status' => 'valid',
            'payment_status' => 'paid',
            'issue_date' => '2026-01-01',
            'supply_date' => '2026-01-01',
            'currency_code' => 'SAR',
            'tax_enabled_snapshot' => true,
            'tax_rate_snapshot' => 15,
            'total_excl_tax' => 100,
            'total_tax' => 15,
            'total_incl_tax' => 115,
            'total_paid' => 115,
            'balance' => 0,
        ]);

        DB::table('invoices')
            ->where('id', $legacyIssued->id)
            ->update([
                'seller_name_snapshot' => null,
                'seller_name_en_snapshot' => null,
                'seller_vat_snapshot' => null,
                'seller_cr_snapshot' => null,
            ]);
        $legacyIssued->refresh();

        $dto = app(PrintDocumentDataBuilder::class)->buildFromInvoice($legacyIssued, 'ar');

        $this->assertContains('seller_name_snapshot', $dto->missingSnapshots);
        $this->assertContains('seller_vat_snapshot', $dto->missingSnapshots);
        $this->assertStringNotContainsString('Khidmah', $dto->seller['name']);
        $this->assertStringNotContainsString('خدمة برو', $dto->seller['name']);
    }

    public function test_show_and_print_render_local_qr_svg_without_external_qr_service_and_untaxed_omits_qr(): void
    {
        $tenant = Tenant::create([
            'name' => 'ورشة محلية',
            'slug' => 'local-qr-'.Str::lower(Str::random(5)),
            'status' => 'active',
            'country' => 'SA',
        ]);
        TenantTaxSetting::create([
            'tenant_id' => $tenant->id,
            'vat_enabled' => true,
            'vat_rate' => 15,
            'tax_number' => '310000000000003',
        ]);
        $center = Center::create([
            'tenant_id' => $tenant->id,
            'name' => 'QR Branch',
            'name_ar' => 'فرع الاختبار',
            'slug' => 'qr-branch',
            'is_active' => true,
        ]);
        Permission::findOrCreate(Permissions::INVOICES_VIEW, 'web');
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
        ]);
        $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);
        setPermissionsTeamId($tenant->id);
        $user->givePermissionTo(Permissions::INVOICES_VIEW);

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'name' => 'سعد',
            'phone' => '+966503333333',
        ]);

        $taxed = Invoice::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-TAX-QR',
            'type' => 'invoice',
            'subtype' => 'simplified',
            'status' => 'valid',
            'payment_status' => 'paid',
            'issue_date' => '2026-11-15',
            'supply_date' => '2026-11-15',
            'currency_code' => 'SAR',
            'tax_enabled_snapshot' => true,
            'tax_rate_snapshot' => 15,
            'total_excl_tax' => 100,
            'total_tax' => 15,
            'total_incl_tax' => 115,
            'total_paid' => 115,
            'balance' => 0,
            'seller_name_snapshot' => 'فرع الاختبار',
            'seller_vat_snapshot' => '310000000000003',
        ]);

        $untaxed = Invoice::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-NOTAX',
            'type' => 'invoice',
            'subtype' => 'simplified',
            'status' => 'valid',
            'payment_status' => 'paid',
            'issue_date' => '2026-11-15',
            'supply_date' => '2026-11-15',
            'currency_code' => 'SAR',
            'tax_enabled_snapshot' => false,
            'tax_rate_snapshot' => 0,
            'total_excl_tax' => 100,
            'total_tax' => 0,
            'total_incl_tax' => 100,
            'total_paid' => 100,
            'balance' => 0,
            'seller_name_snapshot' => 'فرع الاختبار',
        ]);

        $this->actingAs($user)
            ->withSession(['active_center_id' => $center->id])
            ->get(route('app.invoices.show', $taxed))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Invoices/Show')
                ->where('zatca_qr_svg', fn (string $val) => str_starts_with($val, 'data:image/svg+xml;base64,'))
            );

        $this->actingAs($user)
            ->withSession(['active_center_id' => $center->id])
            ->get(route('app.invoices.print', $untaxed))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Invoices/Print')
                ->where('zatca_qr_svg', null)
                ->where('printDocument.qr_mandatory', false)
            );
    }

    public function test_tenant_and_branch_isolation_and_no_cross_invoice_line_leakage(): void
    {
        $tenantA = Tenant::create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a-'.Str::lower(Str::random(5)),
            'status' => 'active',
            'country' => 'SA',
        ]);
        $branchA1 = Center::create(['tenant_id' => $tenantA->id, 'name' => 'A1', 'slug' => 'a1', 'is_active' => true]);
        $branchA2 = Center::create(['tenant_id' => $tenantA->id, 'name' => 'A2', 'slug' => 'a2', 'is_active' => true]);

        $tenantB = Tenant::create([
            'name' => 'Tenant B',
            'slug' => 'tenant-b-'.Str::lower(Str::random(5)),
            'status' => 'active',
            'country' => 'SA',
        ]);
        $branchB1 = Center::create(['tenant_id' => $tenantB->id, 'name' => 'B1', 'slug' => 'b1', 'is_active' => true]);

        Permission::findOrCreate(Permissions::INVOICES_VIEW, 'web');
        $userA = User::factory()->create(['tenant_id' => $tenantA->id, 'current_center_id' => $branchA1->id]);
        $userA->centers()->attach($branchA1->id, ['tenant_id' => $tenantA->id]);
        setPermissionsTeamId($tenantA->id);
        $userA->givePermissionTo(Permissions::INVOICES_VIEW);

        $custA = Customer::create(['tenant_id' => $tenantA->id, 'center_id' => $branchA1->id, 'name' => 'Cust A', 'phone' => '+966504444441']);
        $custB = Customer::create(['tenant_id' => $tenantB->id, 'center_id' => $branchB1->id, 'name' => 'Cust B', 'phone' => '+966504444442']);

        $invA1 = Invoice::create([
            'tenant_id' => $tenantA->id,
            'center_id' => $branchA1->id,
            'customer_id' => $custA->id,
            'invoice_number' => 'INV-A1',
            'type' => 'credit_note',
            'status' => 'valid',
            'payment_status' => 'paid',
            'issue_date' => '2026-11-15',
            'supply_date' => '2026-11-15',
            'currency_code' => 'SAR',
            'total_excl_tax' => 200,
            'total_tax' => 0,
            'total_incl_tax' => 200,
        ]);
        $invA2 = Invoice::create([
            'tenant_id' => $tenantA->id,
            'center_id' => $branchA2->id,
            'customer_id' => $custA->id,
            'invoice_number' => 'INV-A2',
            'type' => 'invoice',
            'status' => 'valid',
            'payment_status' => 'paid',
            'issue_date' => '2026-11-15',
            'supply_date' => '2026-11-15',
            'currency_code' => 'SAR',
            'total_excl_tax' => 500,
            'total_tax' => 0,
            'total_incl_tax' => 500,
        ]);
        $invB1 = Invoice::create([
            'tenant_id' => $tenantB->id,
            'center_id' => $branchB1->id,
            'customer_id' => $custB->id,
            'invoice_number' => 'INV-B1',
            'type' => 'invoice',
            'status' => 'valid',
            'payment_status' => 'paid',
            'issue_date' => '2026-11-15',
            'supply_date' => '2026-11-15',
            'currency_code' => 'SAR',
            'total_excl_tax' => 900,
            'total_tax' => 0,
            'total_incl_tax' => 900,
        ]);

        // Two identical lines on invA1 + one line on invA2
        InvoiceLine::create([
            'invoice_id' => $invA1->id, 'tenant_id' => $tenantA->id, 'center_id' => $branchA1->id,
            'is_part' => true, 'description' => 'بند متطابق', 'qty' => 1, 'unit_price' => 100,
            'line_total_excl_tax' => 100, 'line_total_incl_tax' => 100,
        ]);
        InvoiceLine::create([
            'invoice_id' => $invA1->id, 'tenant_id' => $tenantA->id, 'center_id' => $branchA1->id,
            'is_part' => true, 'description' => 'بند متطابق', 'qty' => 1, 'unit_price' => 100,
            'line_total_excl_tax' => 100, 'line_total_incl_tax' => 100,
        ]);
        InvoiceLine::create([
            'invoice_id' => $invA2->id, 'tenant_id' => $tenantA->id, 'center_id' => $branchA2->id,
            'is_part' => true, 'description' => 'بند فرع آخر', 'qty' => 1, 'unit_price' => 500,
            'line_total_excl_tax' => 500, 'line_total_incl_tax' => 500,
        ]);

        // Authorized invoice loads only its 2 identical lines (no cross-invoice leakage)
        $this->actingAs($userA)
            ->withSession(['active_center_id' => $branchA1->id])
            ->get(route('app.invoices.print', $invA1))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('printDocument.document_type', 'credit_note')
                ->has('printDocument.items', 2)
                ->where('printDocument.items.0.service_name', 'بند متطابق')
                ->where('printDocument.items.1.service_name', 'بند متطابق')
            );

        // Other branch invoice in same tenant -> 403/404 (CenterScoped / Policy)
        $resA2 = $this->actingAs($userA)
            ->withSession(['active_center_id' => $branchA1->id])
            ->get(route('app.invoices.print', $invA2));
        $this->assertContains($resA2->getStatusCode(), [403, 404]);

        // Other tenant invoice -> 403/404 (TenantScoped / Policy)
        $resB1 = $this->actingAs($userA)
            ->withSession(['active_center_id' => $branchA1->id])
            ->get(route('app.invoices.print', $invB1));
        $this->assertContains($resB1->getStatusCode(), [403, 404]);
    }
}
