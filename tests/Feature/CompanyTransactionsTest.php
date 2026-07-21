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
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyTransactionsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Tenant $tenant;

    protected Center $center;

    protected IncomeCategory $revenueCategory;

    protected IncomeCategory $expenseCategory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->center = Center::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
        ]);

        $this->user->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);

        $this->revenueCategory = IncomeCategory::create([
            'tenant_id' => $this->tenant->id,
            'name_ar' => 'إيرادات الإيجار',
            'name_en' => 'Rent Revenues',
            'transaction_type' => 'revenue',
            'is_active' => true,
        ]);

        $this->expenseCategory = IncomeCategory::create([
            'tenant_id' => $this->tenant->id,
            'name_ar' => 'مصروفات الصيانة',
            'name_en' => 'Maintenance Expenses',
            'transaction_type' => 'expense',
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

    /**
     * Test user can create a draft company transaction.
     */
    public function test_user_can_create_draft_company_transaction(): void
    {
        $response = $this->post(route('settings.company.transactions.store'), [
            'title' => 'إيراد إيجار المبنى الرئيسي',
            'transaction_date' => '2026-06-27',
            'transaction_type' => 'revenue',
            'income_category_id' => $this->revenueCategory->id,
            'amount' => 1000.00,
            'is_taxable' => false,
            'tax_amount' => 0.00,
            'total_amount' => 1000.00,
            'notes' => 'تجربة إضافة معاملة',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('company_transactions', [
            'tenant_id' => $this->tenant->id,
            'title' => 'إيراد إيجار المبنى الرئيسي',
            'amount' => 1000.00,
            'status' => 'draft',
        ]);
    }

    /**
     * Test user can update a draft company transaction.
     */
    public function test_user_can_update_draft_company_transaction(): void
    {
        $transaction = CompanyTransaction::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'title' => 'إيراد إيجار',
            'transaction_date' => '2026-06-27',
            'transaction_type' => 'revenue',
            'income_category_id' => $this->revenueCategory->id,
            'amount' => 1000.00,
            'is_taxable' => false,
            'total_amount' => 1000.00,
            'status' => 'draft',
            'updated_by' => $this->user->id,
        ]);

        $response = $this->put(route('settings.company.transactions.update', $transaction->id), [
            'title' => 'إيراد إيجار معدل',
            'transaction_date' => '2026-06-27',
            'transaction_type' => 'revenue',
            'income_category_id' => $this->revenueCategory->id,
            'amount' => 2000.00,
            'is_taxable' => false,
            'tax_amount' => 0.00,
            'total_amount' => 2000.00,
            'notes' => 'تعديل المعاملة',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('company_transactions', [
            'id' => $transaction->id,
            'title' => 'إيراد إيجار معدل',
            'amount' => 2000.00,
        ]);
    }

    /**
     * Test user can delete a draft company transaction.
     */
    public function test_user_can_delete_draft_company_transaction(): void
    {
        $transaction = CompanyTransaction::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'title' => 'إيجار تالف',
            'transaction_date' => '2026-06-27',
            'transaction_type' => 'revenue',
            'income_category_id' => $this->revenueCategory->id,
            'amount' => 500.00,
            'is_taxable' => false,
            'total_amount' => 500.00,
            'status' => 'draft',
            'updated_by' => $this->user->id,
        ]);

        $response = $this->delete(route('settings.company.transactions.destroy', $transaction->id));

        $response->assertRedirect();

        $this->assertSoftDeleted('company_transactions', [
            'id' => $transaction->id,
        ]);
    }

    /**
     * Test taxable transaction requires contact validation.
     */
    public function test_taxable_transaction_requires_contact(): void
    {
        // 1. Taxable without contact should fail
        $response = $this->post(route('settings.company.transactions.store'), [
            'title' => 'إيراد إيجار المبنى الرئيسي',
            'transaction_date' => '2026-06-27',
            'transaction_type' => 'revenue',
            'income_category_id' => $this->revenueCategory->id,
            'amount' => 1000.00,
            'is_taxable' => true,
            'tax_amount' => 150.00,
            'total_amount' => 1150.00,
            'notes' => 'تجربة إضافة معاملة',
        ]);

        $response->assertSessionHasErrors(['contact_id']);

        // 2. Taxable with contact should pass
        $customer = Customer::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'name' => 'العميل الإداري',
            'phone' => '0500000000',
        ]);

        $responsePass = $this->post(route('settings.company.transactions.store'), [
            'title' => 'إيراد إيجار المبنى الرئيسي',
            'transaction_date' => '2026-06-27',
            'transaction_type' => 'revenue',
            'income_category_id' => $this->revenueCategory->id,
            'amount' => 1000.00,
            'is_taxable' => true,
            'tax_amount' => 150.00,
            'total_amount' => 1150.00,
            'contact_type' => 'customer',
            'contact_id' => $customer->id,
            'notes' => 'تجربة إضافة معاملة',
        ]);

        $responsePass->assertRedirect();
    }

    /**
     * Test approving a taxable revenue generates a Sales Invoice.
     */
    public function test_approving_taxable_revenue_generates_sales_invoice(): void
    {
        $customer = Customer::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'name' => 'العميل الإداري',
            'phone' => '0500000000',
        ]);

        $transaction = CompanyTransaction::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'title' => 'بيع خدمات استشارية للشركة',
            'transaction_date' => '2026-06-27',
            'transaction_type' => 'revenue',
            'income_category_id' => $this->revenueCategory->id,
            'amount' => 1000.00,
            'is_taxable' => true,
            'tax_amount' => 150.00,
            'total_amount' => 1150.00,
            'contact_type' => 'customer',
            'contact_id' => $customer->id,
            'status' => 'draft',
            'updated_by' => $this->user->id,
        ]);

        $response = $this->post(route('settings.company.transactions.approve', $transaction->id));

        $response->assertRedirect();

        $transaction->refresh();
        $this->assertEquals('approved', $transaction->status);
        $this->assertNotNull($transaction->invoice_id);

        $this->assertDatabaseHas('invoices', [
            'id' => $transaction->invoice_id,
            'tenant_id' => $this->tenant->id,
            'customer_id' => $customer->id,
            'total_excl_tax' => 1000.00,
            'total_tax' => 150.00,
            'total_incl_tax' => 1150.00,
        ]);

        $this->assertDatabaseHas('invoice_lines', [
            'invoice_id' => $transaction->invoice_id,
            'description' => 'بيع خدمات استشارية للشركة',
            'unit_price' => 1000.00,
            'line_total_incl_tax' => 1150.00,
        ]);
    }

    /**
     * Test approving a taxable expense generates a Purchase Invoice.
     */
    public function test_approving_taxable_expense_generates_purchase_invoice(): void
    {
        $supplier = Supplier::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'name' => 'المورد الإداري',
            'phone' => '0511111111',
        ]);

        $transaction = CompanyTransaction::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'title' => 'شراء خوادم جديدة للشركة',
            'transaction_date' => '2026-06-27',
            'transaction_type' => 'expense',
            'income_category_id' => $this->expenseCategory->id,
            'amount' => 5000.00,
            'is_taxable' => true,
            'tax_amount' => 750.00,
            'total_amount' => 5750.00,
            'contact_type' => 'supplier',
            'contact_id' => $supplier->id,
            'status' => 'draft',
            'updated_by' => $this->user->id,
        ]);

        $response = $this->post(route('settings.company.transactions.approve', $transaction->id));

        $response->assertRedirect();

        $transaction->refresh();
        $this->assertEquals('approved', $transaction->status);
        $this->assertNotNull($transaction->purchase_invoice_id);

        $this->assertDatabaseHas('purchase_invoices', [
            'id' => $transaction->purchase_invoice_id,
            'tenant_id' => $this->tenant->id,
            'supplier_id' => $supplier->id,
            'subtotal' => 5000.00,
            'tax_amount' => 750.00,
            'total' => 5750.00,
        ]);
    }

    /**
     * Test tenancy isolation on company transactions.
     */
    public function test_tenancy_isolation_on_company_transactions(): void
    {
        $otherTenant = Tenant::factory()->create();
        $otherCenter = Center::factory()->create(['tenant_id' => $otherTenant->id]);
        $otherCategory = IncomeCategory::create([
            'tenant_id' => $otherTenant->id,
            'name_ar' => 'أخرى',
            'name_en' => 'Other',
            'transaction_type' => 'revenue',
            'is_active' => true,
        ]);

        $otherTransaction = CompanyTransaction::create([
            'tenant_id' => $otherTenant->id,
            'center_id' => $otherCenter->id,
            'title' => 'صفقة سرية لشركة أخرى',
            'transaction_date' => '2026-06-27',
            'transaction_type' => 'revenue',
            'income_category_id' => $otherCategory->id,
            'amount' => 99999.00,
            'is_taxable' => false,
            'total_amount' => 99999.00,
            'status' => 'draft',
        ]);

        // Attempting to update other tenant's transaction should result in error/not found
        $response = $this->put(route('settings.company.transactions.update', $otherTransaction->id), [
            'title' => 'تعديل مخترق',
            'transaction_date' => '2026-06-27',
            'transaction_type' => 'revenue',
            'income_category_id' => $this->revenueCategory->id,
            'amount' => 10.00,
            'is_taxable' => false,
            'total_amount' => 10.00,
        ]);

        $response->assertStatus(404); // Tenancy isolation throws ModelNotFoundException (404)

        // Let's assert that database record remains unchanged
        $this->assertDatabaseHas('company_transactions', [
            'id' => $otherTransaction->id,
            'title' => 'صفقة سرية لشركة أخرى',
        ]);
    }
}
