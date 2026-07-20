<?php

namespace Tests\Unit\Services;

use App\Models\HR\EmployeeType;
use App\Models\HR\JobTitle;
use App\Models\InventoryUnit;
use App\Models\Tenant;
use App\Services\TenantSetupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TenantSetupServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TenantSetupService $setupService;
    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setupService = new TenantSetupService();
        $this->tenant = Tenant::factory()->create();
    }

    public function test_seed_roles_for_tenant_creates_all_default_roles()
    {
        $this->setupService->seedRolesForTenant($this->tenant->id);

        $roles = Role::where('tenant_id', $this->tenant->id)->pluck('name')->all();

        $this->assertContains('super_admin', $roles);
        $this->assertContains('branch_manager', $roles);
        $this->assertContains('receptionist', $roles);
        $this->assertContains('technician', $roles);
        $this->assertContains('accountant', $roles);
        $this->assertContains('hr', $roles);
        $this->assertContains('employee', $roles);
    }

    public function test_seed_defaults_for_tenant_creates_inventory_units_employee_types_and_job_titles()
    {
        $this->setupService->seedDefaultsForTenant($this->tenant->id);

        $this->assertDatabaseHas('inventory_units', [
            'tenant_id' => $this->tenant->id,
            'name_ar' => 'حبة',
        ]);

        $this->assertDatabaseHas('hr_employee_types', [
            'tenant_id' => $this->tenant->id,
            'name_ar' => 'دائم',
        ]);

        $this->assertDatabaseHas('hr_job_titles', [
            'tenant_id' => $this->tenant->id,
            'name_ar' => 'ميكانيكي',
        ]);
    }

    public function test_seed_defaults_is_idempotent()
    {
        $this->setupService->seedDefaultsForTenant($this->tenant->id);
        $this->setupService->seedDefaultsForTenant($this->tenant->id);

        $count = InventoryUnit::where('tenant_id', $this->tenant->id)->where('name_ar', 'حبة')->count();
        $this->assertEquals(1, $count);
    }
}
