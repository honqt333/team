<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Center;
use App\Models\Supplier;
use App\Models\IncomeCategory;
use App\Models\CompanyTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CompanySettingsAdminUserVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Tenant $tenant;
    protected Center $center;
    protected IncomeCategory $expenseCategory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PermissionsSeeder::class);
        $this->tenant = Tenant::factory()->create();
        $this->center = Center::factory()->create(['tenant_id' => $this->tenant->id]);
        
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
            'password' => Hash::make('secret-12345'),
        ]);

        $this->user->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);

        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
        $superAdminRole = \App\Models\Role::firstOrCreate([
            'tenant_id' => $this->tenant->id,
            'name' => 'super_admin',
            'guard_name' => 'web',
        ]);
        $this->user->assignRole($superAdminRole);

        \App\Models\InventoryUnit::create([
            'tenant_id' => $this->tenant->id,
            'name_ar' => 'حبة',
            'name_en' => 'Piece',
            'is_active' => true,
        ]);

        $this->expenseCategory = IncomeCategory::create([
            'tenant_id' => $this->tenant->id,
            'name_ar' => 'مصروفات الصيانة',
            'name_en' => 'Maintenance Expenses',
            'transaction_type' => 'expense',
            'is_active' => true
        ]);
    }

    public function test_user_can_verify_correct_password(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->postJson(route('settings.company.verify-password'), [
                'password' => 'secret-12345',
            ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
    }

    public function test_user_cannot_verify_incorrect_password(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->postJson(route('settings.company.verify-password'), [
                'password' => 'wrong-password',
            ]);

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
    }

    public function test_saudi_tax_number_validation(): void
    {
        // 1. Invalid tax number (not 15 digits)
        $response1 = $this
            ->actingAs($this->user)
            ->from(route('settings.company'))
            ->put(route('settings.company.update'), [
                'section' => 'vat',
                'data' => [
                    'vat_enabled' => true,
                    'vat_number' => '12345', // Invalid
                    'services_vat_rate' => 15,
                    'parts_vat_rate' => 15,
                ]
            ]);

        $response1->assertRedirect(route('settings.company'));
        $response1->assertSessionHasErrors(['vat_number']);

        // 2. Invalid tax number (15 digits but doesn't start/end with 3)
        $response2 = $this
            ->actingAs($this->user)
            ->from(route('settings.company'))
            ->put(route('settings.company.update'), [
                'section' => 'vat',
                'data' => [
                    'vat_enabled' => true,
                    'vat_number' => '112345678901234', // Doesn't start/end with 3
                    'services_vat_rate' => 15,
                    'parts_vat_rate' => 15,
                ]
            ]);

        $response2->assertRedirect(route('settings.company'));
        $response2->assertSessionHasErrors(['vat_number']);

        // 3. Valid tax number (exactly 15 digits, starts and ends with 3)
        $response3 = $this
            ->actingAs($this->user)
            ->from(route('settings.company'))
            ->put(route('settings.company.update'), [
                'section' => 'vat',
                'data' => [
                    'vat_enabled' => true,
                    'vat_number' => '300000000000003', // Valid
                    'services_vat_rate' => 15,
                    'parts_vat_rate' => 15,
                ]
            ]);

        $response3->assertSessionHasNoErrors();
    }

    public function test_general_admin_supplier_cross_center_uniqueness_on_approval(): void
    {
        // Create another center under same tenant
        $otherCenter = Center::factory()->create(['tenant_id' => $this->tenant->id]);

        // Create General Admin Supplier under that other center
        Supplier::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $otherCenter->id,
            'name' => 'مورد إداري عام',
            'code' => 'SUP-ADMIN-' . $this->tenant->id,
            'is_active' => true,
        ]);

        // Create transaction in current center (center 10)
        $transaction = CompanyTransaction::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'income_category_id' => $this->expenseCategory->id,
            'title' => 'Test Expense Title',
            'transaction_type' => 'expense',
            'amount' => 100,
            'tax_amount' => 15,
            'total_amount' => 115,
            'transaction_date' => now()->format('Y-m-d'),
            'status' => 'draft',
            'is_taxable' => true,
            'notes' => 'Test Transaction',
        ]);

        // Approve transaction in current center
        $response = $this
            ->actingAs($this->user)
            ->post(route('settings.company.transactions.approve', $transaction->id));

        $response->assertRedirect();
        
        // Assert that the general admin supplier was reused, and no duplicate was created
        $this->assertEquals(1, Supplier::withoutGlobalScope('center_scoped')->where('code', 'SUP-ADMIN-' . $this->tenant->id)->count());
    }

    public function test_purchase_invoice_loads_tenant_address_and_maps_contact_on_print(): void
    {
        // 1. Create a detailed address for the tenant
        \App\Models\TenantAddress::create([
            'tenant_id' => $this->tenant->id,
            'building_number' => '1234',
            'street' => 'King Fahd Road',
            'district' => 'Al-Malaz',
            'city' => 'Riyadh',
            'postal_code' => '12345',
            'address_line' => 'Detailed Tenant Address Line',
        ]);

        // 2. Create a customer to act as the contact
        $customer = \App\Models\Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'محمد العميل المشترك',
            'phone' => '0555555555',
            'tax_number' => '300000000000003',
        ]);

        // 3. Create a transaction linked to the customer
        $transaction = CompanyTransaction::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'income_category_id' => $this->expenseCategory->id,
            'title' => 'Test Invoice Contact Title',
            'transaction_type' => 'expense',
            'amount' => 200,
            'tax_amount' => 30,
            'total_amount' => 230,
            'transaction_date' => now()->format('Y-m-d'),
            'status' => 'draft',
            'is_taxable' => true,
            'contact_type' => 'customer',
            'contact_id' => $customer->id,
        ]);

        // Approve it so it creates a Purchase Invoice
        $response = $this->actingAsWithTeam($this->user)
            ->post(route('settings.company.transactions.approve', $transaction->id));

        if (session('error')) {
            dd(session('error'));
        }

        $invoice = \App\Models\PurchaseInvoice::where('tenant_id', $this->tenant->id)
            ->where('total', 230)
            ->firstOrFail();

        // 4. Hit show route
        $responseShow = $this->actingAsWithTeam($this->user)
            ->get(route('app.invoices.purchases.show', $invoice->id));
        $responseShow->assertOk();
        // Assert that 'tenant.address' is loaded
        $invoiceShow = $responseShow->viewData('page')['props']['invoice'];
        $this->assertNotNull($invoiceShow['tenant']['address']);
        $this->assertEquals('1234', $invoiceShow['tenant']['address']['building_number']);

        // 5. Hit print route
        $responsePrint = $this->actingAsWithTeam($this->user)
            ->get(route('app.invoices.purchases.print', $invoice->id));
        $responsePrint->assertOk();
        $invoicePrint = $responsePrint->viewData('page')['props']['invoice'];
        $this->assertNotNull($invoicePrint['tenant']['address']);
        $this->assertEquals('محمد العميل المشترك', $invoicePrint['company_transaction']['contact']['name']);
    }
}
