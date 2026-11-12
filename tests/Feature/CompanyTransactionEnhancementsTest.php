<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\CompanyTransaction;
use App\Models\Customer;
use App\Models\IncomeCategory;
use App\Models\InventoryUnit;
use App\Models\Invoice;
use App\Models\Supplier;
use App\Models\TaxPeriod;
use App\Models\TaxPeriodTransaction;
use App\Models\Tenant;
use App\Models\TenantTaxSetting;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CompanyTransactionEnhancementsTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Center $center;

    private User $user;

    private IncomeCategory $revenueCategory;

    private IncomeCategory $expenseCategory;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ([Permissions::INVOICES_VIEW, Permissions::INVOICES_CREATE] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $this->tenant = Tenant::factory()->create();
        $this->center = Center::factory()->create(['tenant_id' => $this->tenant->id]);

        TenantTaxSetting::create([
            'tenant_id' => $this->tenant->id,
            'vat_enabled' => true,
            'vat_rate' => 15.00,
        ]);

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
        ]);
        $this->user->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
        $this->user->givePermissionTo([Permissions::INVOICES_VIEW, Permissions::INVOICES_CREATE]);
        $this->user->refresh();

        $this->revenueCategory = IncomeCategory::create([
            'tenant_id' => $this->tenant->id,
            'name_ar' => 'خدمات عامة',
            'name_en' => 'General Services',
            'transaction_type' => 'revenue',
            'is_taxable' => true,
            'is_active' => true,
        ]);

        $this->expenseCategory = IncomeCategory::create([
            'tenant_id' => $this->tenant->id,
            'name_ar' => 'أدوات ومهمات',
            'name_en' => 'Tools & Supplies',
            'transaction_type' => 'expense',
            'is_taxable' => true,
            'is_active' => true,
        ]);

        InventoryUnit::firstOrCreate(
            ['tenant_id' => $this->tenant->id],
            [
                'name_ar' => 'حبة',
                'name_en' => 'Piece',
                'is_active' => true,
            ]
        );

        $this->actingAs($this->user);
    }

    public function test_vat_inclusive_calculation_stores_net_and_tax_accurately(): void
    {
        $customer = Customer::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'name' => 'عميل تجريبي',
            'phone' => '0555555555',
            'tax_number' => '300000000000003',
        ]);

        $response = $this->post(route('settings.company.transactions.store'), [
            'title' => 'معاملة شاملة للضريبة',
            'transaction_date' => now()->toDateString(),
            'transaction_type' => 'revenue',
            'income_category_id' => $this->revenueCategory->id,
            'amount' => 115.00,
            'is_taxable' => true,
            'is_vat_inclusive' => true,
            'contact_type' => 'customer',
            'contact_id' => $customer->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $tx = CompanyTransaction::where('title', 'معاملة شاملة للضريبة')->first();
        $this->assertNotNull($tx);
        $this->assertTrue($tx->is_vat_inclusive);
        $this->assertEquals(100.00, (float) $tx->amount);
        $this->assertEquals(15.00, (float) $tx->tax_amount);
        $this->assertEquals(115.00, (float) $tx->total_amount);
    }

    public function test_taxable_expense_requires_supplier_with_tax_number(): void
    {
        // 1. Supplier without tax number fails
        $supplierWithoutVat = Supplier::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'name' => 'مورد غير مسجل ضريبياً',
            'phone' => '0544444444',
            'tax_number' => null,
        ]);

        $responseFail = $this->post(route('settings.company.transactions.store'), [
            'title' => 'شراء قطع غيار',
            'transaction_date' => now()->toDateString(),
            'transaction_type' => 'expense',
            'income_category_id' => $this->expenseCategory->id,
            'amount' => 500.00,
            'is_taxable' => true,
            'contact_type' => 'supplier',
            'contact_id' => $supplierWithoutVat->id,
        ]);

        $responseFail->assertSessionHasErrors(['contact_id']);

        // 2. Supplier with tax number succeeds
        $supplierWithVat = Supplier::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'name' => 'مورد مسجل ضريبياً',
            'phone' => '0533333333',
            'tax_number' => '311111111111113',
        ]);

        $responsePass = $this->post(route('settings.company.transactions.store'), [
            'title' => 'شراء قطع غيار معتمدة',
            'transaction_date' => now()->toDateString(),
            'transaction_type' => 'expense',
            'income_category_id' => $this->expenseCategory->id,
            'amount' => 500.00,
            'is_taxable' => true,
            'contact_type' => 'supplier',
            'contact_id' => $supplierWithVat->id,
        ]);

        $responsePass->assertRedirect();
        $responsePass->assertSessionHasNoErrors();
    }

    public function test_approve_accepts_approved_at_and_applies_to_generated_invoice(): void
    {
        // The clock is frozen so "today" is a known instant and the
        // test never straddles a day boundary while it runs.
        Carbon::setTestNow(Carbon::parse('2026-06-15 10:00:00'));

        try {
            $customer = Customer::create([
                'tenant_id' => $this->tenant->id,
                'center_id' => $this->center->id,
                'name' => 'عميل شركة',
                'phone' => '0566666666',
                'tax_number' => '322222222222223',
            ]);

            $tx = CompanyTransaction::create([
                'tenant_id' => $this->tenant->id,
                'center_id' => $this->center->id,
                'title' => 'استشارة تقنية',
                'transaction_date' => now()->subDays(2)->toDateString(),
                'transaction_type' => 'revenue',
                'income_category_id' => $this->revenueCategory->id,
                'amount' => 200.00,
                'is_taxable' => true,
                'tax_amount' => 30.00,
                'total_amount' => 230.00,
                'contact_type' => 'customer',
                'contact_id' => $customer->id,
                'status' => 'draft',
                'updated_by' => $this->user->id,
            ]);

            // The approval date is deliberately NOT today. Using today's
            // date would hide any implementation that ignores the sent
            // value and stamps `now()` instead — the two would be
            // indistinguishable.
            //
            // It must be on or after the transaction date (2026-06-13),
            // which the controller validates, and before today
            // (2026-06-15).
            $approvedDate = '2026-06-13';

            $response = $this->post(route('settings.company.transactions.approve', $tx->id), [
                'approved_at' => $approvedDate,
            ]);

            $response->assertRedirect();
            $response->assertSessionHasNoErrors();

            $tx->refresh();
            $this->assertSame('approved', $tx->status);
            $this->assertEquals($approvedDate, $tx->approved_at?->format('Y-m-d'));

            $this->assertNotNull($tx->invoice_id);

            // The generated invoice must carry the APPROVAL date, not the
            // moment the approval was processed.
            //
            // `issue_date` is a TIMESTAMP column, so it is compared as a
            // date rather than as a bare string — a bare date never
            // matches a timestamp, which is what made this assertion
            // unusable before.
            $invoice = Invoice::findOrFail($tx->invoice_id);

            $this->assertSame(
                $approvedDate,
                $invoice->issue_date?->format('Y-m-d'),
                'The issued invoice must carry the approval date, not the moment the approval was processed.'
            );

            $this->assertSame(
                'valid',
                $invoice->status,
                'The invoice must be issued, not left as a draft.'
            );

            // The date is frozen at 2026-06-15, so an implementation that
            // stamped `now()` would produce 2026-06-15, not 2026-06-12.
            $this->assertNotSame(
                Carbon::now()->toDateString(),
                $invoice->issue_date?->format('Y-m-d'),
                'The invoice must not carry the processing date.'
            );
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_attachment_upload_and_delete_lifecycle(): void
    {
        $file = UploadedFile::fake()->create('contract.pdf', 100, 'application/pdf');

        $customer = Customer::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'name' => 'عميل للمرفقات',
            'phone' => '0577777777',
        ]);

        $response = $this->post(route('settings.company.transactions.store'), [
            'title' => 'معاملة مع ملف',
            'transaction_date' => now()->toDateString(),
            'transaction_type' => 'revenue',
            'income_category_id' => $this->revenueCategory->id,
            'amount' => 100.00,
            'is_taxable' => false,
            'contact_type' => 'customer',
            'contact_id' => $customer->id,
            'attachment' => $file,
        ]);

        $response->assertRedirect();
        $tx = CompanyTransaction::where('title', 'معاملة مع ملف')->first();
        $this->assertNotNull($tx);
        $this->assertNotNull($tx->attachment_path);
        Storage::disk('public')->assertExists($tx->attachment_path);
        $this->assertNotNull($tx->attachment_url);

        // Delete attachment via standalone route
        $deleteResponse = $this->delete(route('settings.company.transactions.attachment.delete', $tx->id));
        $deleteResponse->assertRedirect();

        $tx->refresh();
        $this->assertNull($tx->attachment_path);
        $this->assertNull($tx->attachment_url);

        // Upload attachment via standalone route
        $newFile = UploadedFile::fake()->image('receipt.png');
        $uploadResponse = $this->post(route('settings.company.transactions.attachment.upload', $tx->id), [
            'attachment' => $newFile,
        ]);
        $uploadResponse->assertRedirect();

        $tx->refresh();
        $this->assertNotNull($tx->attachment_path);
        Storage::disk('public')->assertExists($tx->attachment_path);
    }

    public function test_income_category_linked_data_detection(): void
    {
        $this->assertTrue($this->revenueCategory->is_taxable);

        $customer = Customer::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'name' => 'عميل ربط الفئات',
            'phone' => '0588888888',
        ]);

        $this->assertFalse($this->revenueCategory->hasLinkedData());

        $tx = CompanyTransaction::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'title' => 'معاملة تابعة للفئة',
            'transaction_date' => now()->toDateString(),
            'transaction_type' => 'revenue',
            'income_category_id' => $this->revenueCategory->id,
            'amount' => 100.00,
            'is_taxable' => false,
            'total_amount' => 100.00,
            'contact_type' => 'customer',
            'contact_id' => $customer->id,
            'status' => 'draft',
            'updated_by' => $this->user->id,
        ]);

        $this->assertTrue($this->revenueCategory->fresh()->hasLinkedData());
    }

    public function test_tax_period_accessor_returns_matching_period(): void
    {
        $period = TaxPeriod::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'period_key' => '2026-Q4',
            'name' => 'الربع الرابع 2026',
            'year' => 2026,
            'year_period' => 'Q4',
            'period_type' => 'quarterly',
            'start_date' => '2026-10-01',
            'end_date' => '2026-12-31',
            'status' => 'open',
        ]);

        $tx = CompanyTransaction::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'title' => 'معاملة ضمن فترة ضريبية',
            'transaction_date' => '2026-11-01',
            'transaction_type' => 'revenue',
            'income_category_id' => $this->revenueCategory->id,
            'amount' => 100.00,
            'is_taxable' => true,
            'tax_amount' => 15.00,
            'total_amount' => 115.00,
            'status' => 'approved',
            'updated_by' => $this->user->id,
        ]);

        TaxPeriodTransaction::create([
            'tax_period_id' => $period->id,
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'transaction_type' => 'company_transaction',
            'transaction_id' => $tx->id,
            'ref_no' => 'COMP-'.$tx->id,
            'transaction_date' => '2026-11-01',
            'subtotal' => 100.00,
            'vat_rate' => 15.00,
            'vat_amount' => 15.00,
            'total_amount' => 115.00,
            'is_income' => true,
            'is_selected' => true,
        ]);

        $tx->refresh();
        $taxPeriodData = $tx->tax_period;
        $this->assertNotNull($taxPeriodData);
        $this->assertEquals($period->id, $taxPeriodData['id']);
        $this->assertEquals('الربع الرابع 2026', $taxPeriodData['name']);
    }

    public function test_contact_search_returns_tax_number_and_vat_flag(): void
    {
        $customer = Customer::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'name' => 'شركة الأفق للاستشارات',
            'phone' => '0599999999',
            'tax_number' => '399999999999993',
        ]);

        $response = $this->getJson(route('settings.company.contacts.search', ['search' => 'الأفق']));
        $response->assertOk();

        $data = $response->json();
        $this->assertNotEmpty($data);
        $this->assertSame('399999999999993', $data[0]['tax_number']);
        $this->assertTrue($data[0]['has_vat']);
    }
}
