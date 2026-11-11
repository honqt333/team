<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\CompanyTransaction;
use App\Models\Customer;
use App\Models\IncomeCategory;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\TenantTaxSetting;
use App\Models\User;
use App\Services\Reports\FinancialSummaryReportService;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CompanyTransactionsAndFinancialSummaryTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Center $center;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::findOrCreate(Permissions::INVOICES_VIEW, 'web');
        Permission::findOrCreate(Permissions::INVOICES_CREATE, 'web');

        $this->tenant = Tenant::create([
            'name' => 'Test Workshop Co',
            'slug' => 'test-workshop-co',
            'country' => 'SA',
            'currency' => 'SAR',
            'timezone' => 'Asia/Riyadh',
            'is_active' => true,
        ]);

        TenantTaxSetting::create([
            'tenant_id' => $this->tenant->id,
            'vat_enabled' => true,
            'vat_rate' => 15,
            'tax_number' => '300000000000003',
        ]);

        $this->center = Center::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Main Branch',
            'slug' => 'main-branch',
            'is_active' => true,
        ]);

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
            'is_active' => true,
        ]);

        $this->user->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
        $this->user->givePermissionTo([
            Permissions::INVOICES_VIEW,
            Permissions::INVOICES_CREATE,
        ]);
        $this->user->refresh();
    }

    public function test_vat_inclusive_admin_revenue_can_be_created_approved_and_reflected_in_financial_summary(): void
    {
        $category = IncomeCategory::create([
            'tenant_id' => $this->tenant->id,
            'name_ar' => 'إيرادات إدارية',
            'name_en' => 'Admin Revenue',
            'transaction_type' => 'revenue',
            'is_taxable' => true,
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'name' => 'شركة الاختبار',
            'phone' => '0551234567',
            'tax_number' => '310000000000003',
            'is_active' => true,
        ]);

        // Store VAT-inclusive admin revenue of 115.00 SAR (net 100.00 + VAT 15.00)
        $response = $this->actingAs($this->user)->post(route('settings.company.transactions.store'), [
            'title' => 'إيجار لوحة إعلانية',
            'transaction_date' => now()->toDateString(),
            'transaction_type' => 'revenue',
            'income_category_id' => $category->id,
            'amount' => 115,
            'gross_amount' => 115,
            'is_taxable' => true,
            'is_vat_inclusive' => true,
            'contact_type' => 'customer',
            'contact_id' => $customer->id,
            'center_id' => null,
        ]);

        $response->assertRedirect();

        $tx = CompanyTransaction::where('tenant_id', $this->tenant->id)->firstOrFail();
        $this->assertNull($tx->center_id);
        $this->assertTrue($tx->is_taxable);
        $this->assertTrue($tx->is_vat_inclusive);
        $this->assertEquals(100.00, (float) $tx->amount);
        $this->assertEquals(15.00, (float) $tx->tax_amount);
        $this->assertEquals(115.00, (float) $tx->total_amount);

        // Approve the admin revenue transaction
        $approveResponse = $this->actingAs($this->user)->post(route('settings.company.transactions.approve', $tx->id), [
            'approved_at' => now()->toDateString(),
        ]);

        $approveResponse->assertRedirect();
        $tx->refresh();

        $this->assertEquals('approved', $tx->status);
        $this->assertNotNull($tx->invoice_id);
        $this->assertEquals(now()->toDateString(), $tx->approved_at?->format('Y-m-d'));

        // Verify Administration statement in FinancialSummaryReportService includes this transaction
        /** @var FinancialSummaryReportService $service */
        $service = app(FinancialSummaryReportService::class);
        $adminStatement = $service->computeCenterFinancialStatement(
            (int) $this->tenant->id,
            null,
            now()->subDays(5)->toDateString(),
            now()->addDays(1)->toDateString()
        );

        $this->assertEquals(100.00, $adminStatement['incomeSumSubTotal']);
        $this->assertEquals(15.00, $adminStatement['incomeSumVATAmount']);
        $this->assertEquals(115.00, $adminStatement['incomeSumNetAmount']);
        $this->assertEquals(100.00, $adminStatement['profit']);
    }

    public function test_taxable_expense_requires_supplier_with_vat_number(): void
    {
        $category = IncomeCategory::create([
            'tenant_id' => $this->tenant->id,
            'name_ar' => 'مصاريف كهرباء',
            'name_en' => 'Electricity Expense',
            'transaction_type' => 'expense',
            'is_taxable' => true,
            'is_active' => true,
        ]);

        $supplierWithoutVat = Supplier::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'name' => 'مورد بدون ضريبة',
            'code' => 'SUP-NO-VAT',
            'tax_number' => null,
            'is_active' => true,
        ]);

        $responseFail = $this->actingAs($this->user)->post(route('settings.company.transactions.store'), [
            'title' => 'فاتورة صيانة تكييف',
            'transaction_date' => now()->toDateString(),
            'transaction_type' => 'expense',
            'income_category_id' => $category->id,
            'amount' => 200,
            'is_taxable' => true,
            'contact_type' => 'supplier',
            'contact_id' => $supplierWithoutVat->id,
        ]);

        $responseFail->assertSessionHasErrors('contact_id');

        // Now give the supplier a valid VAT number
        $supplierWithoutVat->update(['tax_number' => '300999999900003']);

        $responseSuccess = $this->actingAs($this->user)->post(route('settings.company.transactions.store'), [
            'title' => 'فاتورة صيانة تكييف',
            'transaction_date' => now()->toDateString(),
            'transaction_type' => 'expense',
            'income_category_id' => $category->id,
            'amount' => 200,
            'is_taxable' => true,
            'is_vat_inclusive' => false,
            'contact_type' => 'supplier',
            'contact_id' => $supplierWithoutVat->id,
        ]);

        $responseSuccess->assertSessionHasNoErrors();
        $tx = CompanyTransaction::where('title', 'فاتورة صيانة تكييف')->firstOrFail();
        $this->assertEquals(200.00, (float) $tx->amount);
        $this->assertEquals(30.00, (float) $tx->tax_amount);
        $this->assertEquals(230.00, (float) $tx->total_amount);
    }
}
