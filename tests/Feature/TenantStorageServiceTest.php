<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Billing\Plan;
use App\Models\Billing\Subscription;
use App\Models\Center;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderAttachment;
use App\Services\TenantStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TenantStorageServiceTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Center $center;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->center = Center::factory()->create(['tenant_id' => $this->tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
            'is_active' => true,
        ]);
        $this->user->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);

        $role = Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'web',
            'tenant_id' => $this->tenant->id,
        ]);
        $this->user->assignRole($role);
    }

    public function test_default_storage_allocation_is_ten_gb(): void
    {
        $allocatedGb = TenantStorageService::getAllocatedStorageGb($this->tenant);
        $allocatedBytes = TenantStorageService::getAllocatedStorageBytes($this->tenant);

        $this->assertEquals(10, $allocatedGb);
        $this->assertEquals(10 * 1024 * 1024 * 1024, $allocatedBytes);
    }

    public function test_plan_specific_storage_limit_is_respected(): void
    {
        $plan = Plan::create([
            'name' => 'Custom Pro Plan',
            'name_ar' => 'باقة مخصصة',
            'name_en' => 'Custom Plan',
            'slug' => 'custom-pro-plan',
            'price_monthly' => 199,
            'price_yearly' => 1990,
            'trial_days' => 14,
            'limits' => ['storage_gb' => 50, 'max_users' => 10],
            'is_active' => true,
        ]);

        Subscription::create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => 'yearly',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addYear(),
        ]);

        $allocatedGb = TenantStorageService::getAllocatedStorageGb($this->tenant);
        $this->assertEquals(50, $allocatedGb);
    }

    public function test_storage_calculation_aggregates_attachments(): void
    {
        Storage::fake('public');

        $workOrder = WorkOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
        ]);

        // Create 2 attachments with known sizes
        WorkOrderAttachment::create([
            'tenant_id' => $this->tenant->id,
            'work_order_id' => $workOrder->id,
            'file_name' => 'doc1.pdf',
            'path' => 'attachments/doc1.pdf',
            'file_type' => 'application/pdf',
            'file_size' => 2048000, // ~2MB
            'user_id' => $this->user->id,
        ]);

        WorkOrderAttachment::create([
            'tenant_id' => $this->tenant->id,
            'work_order_id' => $workOrder->id,
            'file_name' => 'doc2.pdf',
            'path' => 'attachments/doc2.pdf',
            'file_type' => 'application/pdf',
            'file_size' => 3145728, // ~3MB
            'user_id' => $this->user->id,
        ]);

        TenantStorageService::clearCache($this->tenant->id);
        $summary = TenantStorageService::getStorageUsageSummary($this->tenant, true);

        $this->assertEquals(5193728, $summary['used_bytes']);
        $this->assertEquals(4.95, $summary['used_mb']);
        $this->assertTrue(TenantStorageService::hasSufficientStorage($this->tenant, 1024));
        $this->assertFalse($summary['is_exceeded']);
    }

    public function test_tenant_storage_usage_accessor_and_system_settings_integration(): void
    {
        $summary = $this->tenant->storage_usage;

        $this->assertIsArray($summary);
        $this->assertArrayHasKey('used_bytes', $summary);
        $this->assertArrayHasKey('allocated_gb', $summary);
        $this->assertArrayHasKey('usage_percentage', $summary);

        $this->actingAs($this->user)
            ->get(route('settings.system', ['section' => 'storage']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Settings/System/Index')
                ->where('activeSection', 'storage')
                ->has('storage_usage')
            );

        $this->actingAs($this->user)
            ->get(route('settings.storage'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Settings/System/Index')
                ->where('activeSection', 'storage')
                ->has('storage_usage')
            );

        $this->actingAs($this->user)
            ->get(route('app.billing.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Settings/Billing/Index')
                ->missing('storage_usage')
            );
    }
}
