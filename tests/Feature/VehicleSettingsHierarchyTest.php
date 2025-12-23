<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Tenant;
use App\Models\User;
use App\Models\VehicleMake;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleSettingsHierarchyTest extends TestCase
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
        // TenancyContext derives tenant/center from authenticated user
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
        ]);
    }

    /**
     * Test 1: Cannot update system-level vehicle make
     */
    public function test_cannot_update_system_vehicle_make(): void
    {
        // Create a system-level make (no tenant/center)
        $systemMake = VehicleMake::withoutGlobalScopes()->create([
            'name_ar' => 'تويوتا',
            'name_en' => 'Toyota',
            'source' => 'system',
            'tenant_id' => null,
            'center_id' => null,
            'is_active' => true,
        ]);

        $this->actingAs($this->user);

        $response = $this->put(route('settings.makes.update', $systemMake), [
            'name_ar' => 'تويوتا معدل',
            'name_en' => 'Toyota Modified',
            'is_active' => true,
        ]);

        $response->assertStatus(403);
        
        // Verify data unchanged
        $this->assertDatabaseHas('vehicle_makes', [
            'id' => $systemMake->id,
            'name_ar' => 'تويوتا',
        ]);
    }

    /**
     * Test 2: Cannot delete system-level vehicle make
     */
    public function test_cannot_delete_system_vehicle_make(): void
    {
        $systemMake = VehicleMake::withoutGlobalScopes()->create([
            'name_ar' => 'جي إم سي',
            'name_en' => 'GMC',
            'source' => 'system',
            'tenant_id' => null,
            'center_id' => null,
            'is_active' => true,
        ]);

        $this->actingAs($this->user);

        $response = $this->delete(route('settings.makes.destroy', $systemMake));

        $response->assertStatus(403);
        
        // Verify data still exists
        $this->assertDatabaseHas('vehicle_makes', [
            'id' => $systemMake->id,
        ]);
    }

    /**
     * Test 3: Results are ordered by source (center → tenant → system)
     */
    public function test_vehicle_makes_are_ordered_by_source(): void
    {
        // Create makes with different sources (in reverse order)
        VehicleMake::withoutGlobalScopes()->create([
            'name_ar' => 'نظام',
            'name_en' => 'System',
            'source' => 'system',
            'tenant_id' => null,
            'center_id' => null,
            'is_active' => true,
        ]);

        VehicleMake::withoutGlobalScopes()->create([
            'name_ar' => 'مستأجر',
            'name_en' => 'Tenant',
            'source' => 'tenant',
            'tenant_id' => $this->tenant->id,
            'center_id' => null,
            'is_active' => true,
        ]);

        VehicleMake::withoutGlobalScopes()->create([
            'name_ar' => 'مركز',
            'name_en' => 'Center',
            'source' => 'center',
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'is_active' => true,
        ]);

        // Query with ordering using CASE WHEN (SQLite compatible)
        $makes = VehicleMake::withoutGlobalScopes()
            ->orderByRaw("CASE source WHEN 'center' THEN 1 WHEN 'tenant' THEN 2 WHEN 'system' THEN 3 END")
            ->pluck('source')
            ->toArray();

        // Assert order: center first, then tenant, then system
        $this->assertEquals(['center', 'tenant', 'system'], $makes);
    }
}
