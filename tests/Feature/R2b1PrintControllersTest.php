<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PaymentType;
use App\Models\Center;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Part;
use App\Models\Payment;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturnInvoice;
use App\Models\PurchaseReturnInvoiceLine;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\TenantTaxSetting;
use App\Models\TenantWebsite;
use App\Models\User;
use App\Support\CustomerPortalSession;
use App\Support\Permissions;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class R2b1PrintControllersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach ([
            Permissions::RETURNS_VIEW,
            Permissions::INVOICES_VIEW,
        ] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
    }

    public function test_purchase_return_print_loads_center_tenant_and_real_refund_amounts_and_enforces_tenant_isolation(): void
    {
        $tenantA = Tenant::create([
            'name' => 'شركة الصيانة أ',
            'slug' => 'tenant-a-'.Str::lower(Str::random(5)),
            'status' => 'active',
            'country' => 'SA',
        ]);
        TenantTaxSetting::create([
            'tenant_id' => $tenantA->id,
            'vat_enabled' => true,
            'vat_rate' => 15,
            'services_inclusive' => false,
            'tax_number' => '310000000000003',
        ]);
        $centerA = Center::create([
            'tenant_id' => $tenantA->id,
            'name' => 'Main Branch A',
            'name_ar' => 'الفرع الرئيسي أ',
            'name_en' => 'Main Branch A',
            'slug' => 'br-a-'.Str::lower(Str::random(5)),
            'is_active' => true,
        ]);

        $userA = User::factory()->create([
            'tenant_id' => $tenantA->id,
            'current_center_id' => $centerA->id,
        ]);
        $userA->centers()->attach($centerA->id, ['tenant_id' => $tenantA->id]);
        setPermissionsTeamId($tenantA->id);
        $userA->givePermissionTo(Permissions::RETURNS_VIEW);

        $supplier = Supplier::create([
            'tenant_id' => $tenantA->id,
            'center_id' => $centerA->id,
            'name' => 'مورد القطع الرئيسي',
            'phone' => '0551234567',
        ]);
        $part = Part::create([
            'tenant_id' => $tenantA->id,
            'center_id' => $centerA->id,
            'name_ar' => 'فلتر زيت',
            'name_en' => 'Oil Filter',
            'sku' => 'OIL-FLT-01',
            'cost_price' => 100,
            'default_sale_price' => 150,
            'min_sale_price' => 120,
            'qty_on_hand' => 10,
        ]);

        $purchaseInvoice = PurchaseInvoice::create([
            'tenant_id' => $tenantA->id,
            'center_id' => $centerA->id,
            'supplier_id' => $supplier->id,
            'code' => 'PINV-0001',
            'invoice_number' => 'SUP-100',
            'issue_date' => '2026-11-10',
            'status' => PurchaseInvoice::STATUS_OPEN,
            'subtotal' => 200,
            'tax_amount' => 30,
            'total' => 230,
            'balance' => 230,
        ]);

        $returnInvoice = PurchaseReturnInvoice::create([
            'tenant_id' => $tenantA->id,
            'center_id' => $centerA->id,
            'purchase_invoice_id' => $purchaseInvoice->id,
            'code' => 'PRET-0001',
            'return_date' => '2026-11-15',
            'subtotal' => 200,
            'tax_amount' => 30,
            'total' => 230,
            'create_debit_note' => true,
        ]);
        PurchaseReturnInvoiceLine::create([
            'purchase_return_invoice_id' => $returnInvoice->id,
            'part_id' => $part->id,
            'qty' => 2,
            'unit_cost' => 100,
            'tax_rate' => 15,
            'tax_amount' => 30,
            'total' => 230,
        ]);

        // Partial refund of 100 out of 230
        Payment::create([
            'tenant_id' => $tenantA->id,
            'center_id' => $centerA->id,
            'purchase_invoice_id' => $purchaseInvoice->id,
            'type' => PaymentType::REFUND->value,
            'amount' => 100,
            'payment_method' => 'bank_transfer',
            'payment_date' => '2026-11-15',
            'received_by' => $userA->id,
            'notes' => 'Refund for PRET-0001',
        ]);

        $this->actingAs($userA)
            ->withSession(['active_center_id' => $centerA->id])
            ->get(route('app.invoices.purchases.returns.print', $returnInvoice))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Purchasing/Invoices/ReturnPrint')
                ->where('returnInvoice.code', 'PRET-0001')
                ->where('returnInvoice.refunded_amount', 100)
                ->where('returnInvoice.remaining_refund', 130)
                ->where('returnInvoice.center.name_ar', 'الفرع الرئيسي أ')
                ->where('returnInvoice.tenant.tax_settings.vat_enabled', true)
                ->where('returnInvoice.purchase_invoice.supplier.name', 'مورد القطع الرئيسي')
            );

        // Cross-tenant isolation
        $tenantB = Tenant::create([
            'name' => 'شركة أخرى ب',
            'slug' => 'tenant-b-'.Str::lower(Str::random(5)),
            'status' => 'active',
            'country' => 'SA',
        ]);
        $centerB = Center::create([
            'tenant_id' => $tenantB->id,
            'name' => 'Branch B',
            'slug' => 'br-b-'.Str::lower(Str::random(5)),
            'is_active' => true,
        ]);
        $userB = User::factory()->create([
            'tenant_id' => $tenantB->id,
            'current_center_id' => $centerB->id,
        ]);
        $userB->centers()->attach($centerB->id, ['tenant_id' => $tenantB->id]);
        setPermissionsTeamId($tenantB->id);
        $userB->givePermissionTo(Permissions::RETURNS_VIEW);

        $this->actingAs($userB)
            ->withSession(['active_center_id' => $centerB->id])
            ->get(route('app.invoices.purchases.returns.print', $returnInvoice))
            ->assertNotFound();
    }

    public function test_portal_invoice_show_renders_long_invoice_in_ar_and_en_and_enforces_tenant_isolation(): void
    {
        $tenant = Tenant::create([
            'name' => 'مركز البوابة',
            'slug' => 'portal-shop-'.Str::lower(Str::random(5)),
            'status' => 'active',
            'country' => 'SA',
        ]);
        TenantWebsite::factory()->for($tenant)->published()->create();
        $center = Center::create([
            'tenant_id' => $tenant->id,
            'name' => 'Portal Center',
            'name_ar' => 'مركز البوابة الرئيسي',
            'name_en' => 'Portal Main Center',
            'slug' => 'prt-1-'.Str::lower(Str::random(5)),
            'is_active' => true,
        ]);
        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'name' => 'عميل البوابة',
            'phone' => '0509988776',
        ]);

        $invoice = Invoice::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-PRT-001',
            'type' => 'invoice',
            'subtype' => 'simplified',
            'status' => 'valid',
            'payment_status' => 'unpaid',
            'issue_date' => '2026-11-15',
            'supply_date' => '2026-11-15',
            'currency_code' => 'SAR',
            'tax_enabled_snapshot' => true,
            'tax_rate_snapshot' => 15,
            'pricing_mode_snapshot' => 'exclusive',
            'total_excl_tax' => 2500,
            'total_tax' => 375,
            'total_incl_tax' => 2875,
            'total_paid' => 0,
            'balance' => 2875,
            'customer_name_snapshot' => 'عميل البوابة',
        ]);

        for ($i = 1; $i <= 25; $i++) {
            InvoiceLine::create([
                'invoice_id' => $invoice->id,
                'description' => "بند صيانة رقم {$i}",
                'qty' => 1,
                'unit_price' => 100,
                'discount_amount' => 0,
                'is_taxable' => true,
                'tax_rate_snapshot' => 15,
                'tax_amount' => 15,
                'line_total_excl_tax' => 100,
                'line_total_incl_tax' => 115,
            ]);
        }

        // Login as portal customer
        $portalSession = [
            'customer_id' => $customer->id,
            'tenant_id' => $tenant->id,
            'created_at' => Carbon::now()->toIso8601String(),
            'last_seen_at' => Carbon::now()->toIso8601String(),
        ];

        $this->withSession([CustomerPortalSession::key() => $portalSession])
            ->get("/{$tenant->slug}/portal/invoice/{$invoice->id}?lang=ar")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Portal/Invoices/Show')
                ->where('locale.current', 'ar')
                ->where('invoice.invoice_number', 'INV-PRT-001')
                ->where('invoice.total_incl_tax', 2875)
                ->has('invoice.lines', 25)
            );

        $this->withSession([CustomerPortalSession::key() => $portalSession])
            ->get("/{$tenant->slug}/portal/invoice/{$invoice->id}?lang=en")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Portal/Invoices/Show')
                ->where('locale.current', 'en')
                ->where('invoice.invoice_number', 'INV-PRT-001')
                ->has('invoice.lines', 25)
            );

        // Cross-tenant portal access must fail
        $otherTenant = Tenant::create([
            'name' => 'مركز آخر',
            'slug' => 'other-portal-'.Str::lower(Str::random(5)),
            'status' => 'active',
            'country' => 'SA',
        ]);
        TenantWebsite::factory()->for($otherTenant)->published()->create();

        $this->withSession([CustomerPortalSession::key() => $portalSession])
            ->get("/{$otherTenant->slug}/portal/invoice/{$invoice->id}")
            ->assertRedirect();
    }

    public function test_staff_invoice_print_loads_all_invoice_lines_even_when_line_center_id_is_null(): void
    {
        $tenant = Tenant::create([
            'name' => 'مركز الفواتير المباشرة',
            'slug' => 'direct-print-'.Str::lower(Str::random(5)),
            'status' => 'active',
            'country' => 'SA',
        ]);
        $center = Center::create([
            'tenant_id' => $tenant->id,
            'name' => 'Direct Branch',
            'name_ar' => 'فرع المبيعات المباشرة',
            'slug' => 'direct-branch',
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
            'name' => 'عميل مبيعات مباشرة',
            'phone' => '+966509998877',
        ]);

        $invoice = Invoice::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-DIR-PRINT-25',
            'type' => 'invoice',
            'subtype' => 'simplified',
            'status' => 'valid',
            'payment_status' => 'unpaid',
            'issue_date' => '2026-11-15',
            'supply_date' => '2026-11-15',
            'currency_code' => 'SAR',
            'tax_enabled_snapshot' => false,
            'tax_rate_snapshot' => 0,
            'pricing_mode_snapshot' => 'exclusive',
            'total_excl_tax' => 2500.00,
            'total_tax' => 0.00,
            'total_incl_tax' => 2500.00,
            'total_paid' => 0.00,
            'balance' => 2500.00,
            'customer_name_snapshot' => 'عميل مبيعات مباشرة',
        ]);

        for ($i = 1; $i <= 25; $i++) {
            InvoiceLine::create([
                'invoice_id' => $invoice->id,
                'tenant_id' => $tenant->id,
                'center_id' => null,
                'is_part' => true,
                'description' => "بند مباشر {$i}",
                'qty' => 1,
                'unit_price' => 100.00,
                'discount_amount' => 0,
                'is_taxable' => false,
                'tax_rate_snapshot' => 0,
                'tax_amount' => 0.00,
                'line_total_excl_tax' => 100.00,
                'line_total_incl_tax' => 100.00,
            ]);
        }

        $this->actingAs($user)
            ->withSession(['active_center_id' => $center->id])
            ->get(route('app.invoices.print', $invoice))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Invoices/Print')
                ->where('invoice.invoice_number', 'INV-DIR-PRINT-25')
                ->where('invoice.total_incl_tax', '2500.00')
                ->has('invoice.lines', 25)
            );
    }
}
