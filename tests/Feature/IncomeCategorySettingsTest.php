<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\IncomeCategory;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IncomeCategorySettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Tenant $tenant;
    protected Center $center;

    protected function setUp(): void
    {
        parent::setUp();

        // Create tenant and center
        $this->tenant = Tenant::factory()->create();
        $this->center = Center::factory()->create(['tenant_id' => $this->tenant->id]);
        
        // Create user with tenant context
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
        ]);

        // Attach user to center to satisfy EnsureCenterContext middleware
        $this->user->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);
    }

    /**
     * Test 1: User can view list of income categories
     */
    public function test_user_can_view_income_categories(): void
    {
        $category = IncomeCategory::create([
            'tenant_id' => $this->tenant->id,
            'name_ar' => 'صيانة سيارات',
            'name_en' => 'Car Maintenance',
            'transaction_type' => 'revenue',
            'is_active' => true,
        ]);

        $this->actingAs($this->user);

        $response = $this->get(route('settings.income-categories.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Settings/System/Index')
            ->has('income_categories')
            ->where('activeSection', 'income-categories')
        );
    }

    /**
     * Test 2: User can create a new income category
     */
    public function test_user_can_create_income_category(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('settings.income-categories.store'), [
            'name_ar' => 'مبيعات قطع غيار',
            'name_en' => 'Spare Parts Sales',
            'transaction_type' => 'revenue',
            'is_active' => true,
        ]);

        $response->assertRedirect();
        
        // Verify database contains the created category with the correct tenant_id
        $this->assertDatabaseHas('income_categories', [
            'tenant_id' => $this->tenant->id,
            'name_ar' => 'مبيعات قطع غيار',
            'name_en' => 'Spare Parts Sales',
            'transaction_type' => 'revenue',
            'is_active' => 1,
            'updated_by' => $this->user->id,
        ]);
    }

    /**
     * Test 3: User can update an income category
     */
    public function test_user_can_update_income_category(): void
    {
        $category = IncomeCategory::create([
            'tenant_id' => $this->tenant->id,
            'name_ar' => 'رواتب الموظفين',
            'name_en' => 'Employees Salaries',
            'transaction_type' => 'expense',
            'is_active' => true,
        ]);

        $this->actingAs($this->user);

        $response = $this->put(route('settings.income-categories.update', $category), [
            'name_ar' => 'رواتب الموظفين والعمال',
            'name_en' => 'Staff Salaries',
            'transaction_type' => 'expense',
            'is_active' => false,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('income_categories', [
            'id' => $category->id,
            'name_ar' => 'رواتب الموظفين والعمال',
            'name_en' => 'Staff Salaries',
            'is_active' => 0,
            'updated_by' => $this->user->id,
        ]);
    }

    /**
     * Test 4: User can toggle active status
     */
    public function test_user_can_toggle_income_category_status(): void
    {
        $category = IncomeCategory::create([
            'tenant_id' => $this->tenant->id,
            'name_ar' => 'إيجار المحل',
            'name_en' => 'Shop Rent',
            'transaction_type' => 'expense',
            'is_active' => true,
        ]);

        $this->actingAs($this->user);

        $response = $this->patch(route('settings.income-categories.toggle', $category));

        $response->assertRedirect();
        
        $this->assertDatabaseHas('income_categories', [
            'id' => $category->id,
            'is_active' => 0,
        ]);
    }

    /**
     * Test 5: User can delete an income category (soft delete)
     */
    public function test_user_can_delete_income_category(): void
    {
        $category = IncomeCategory::create([
            'tenant_id' => $this->tenant->id,
            'name_ar' => 'فاتورة الكهرباء',
            'name_en' => 'Electricity Bill',
            'transaction_type' => 'expense',
            'is_active' => true,
        ]);

        $this->actingAs($this->user);

        $response = $this->delete(route('settings.income-categories.destroy', $category));

        $response->assertRedirect();

        // Verify it was soft deleted
        $this->assertSoftDeleted('income_categories', [
            'id' => $category->id,
        ]);
    }

    /**
     * Test 6: Tenancy isolation prevents modifying categories of other tenants
     */
    public function test_tenancy_isolation_on_income_categories(): void
    {
        // Create another tenant
        $otherTenant = Tenant::factory()->create();
        
        // Create category for the other tenant
        $otherCategory = IncomeCategory::create([
            'tenant_id' => $otherTenant->id,
            'name_ar' => 'خدمة غسيل السيارات',
            'name_en' => 'Car Wash Service',
            'transaction_type' => 'revenue',
            'is_active' => true,
        ]);

        // Login as our user (from the first tenant)
        $this->actingAs($this->user);

        // Try to update other tenant's category
        $response = $this->put(route('settings.income-categories.update', $otherCategory), [
            'name_ar' => 'اسم مخترق',
            'name_en' => 'Hacked Name',
            'transaction_type' => 'revenue',
            'is_active' => true,
        ]);

        // Laravel automatically throws 404 because the global scope in TenantScoped traits
        // filters out categories belonging to other tenants.
        $response->assertStatus(404);

        // Try to toggle active status of other tenant's category
        $responseToggle = $this->patch(route('settings.income-categories.toggle', $otherCategory));
        $responseToggle->assertStatus(404);

        // Try to delete other tenant's category
        $responseDelete = $this->delete(route('settings.income-categories.destroy', $otherCategory));
        $responseDelete->assertStatus(404);
    }
}
