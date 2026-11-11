<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Models\Billing\Plan;
use App\Models\Billing\Subscription;
use App\Models\Center;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class BranchesIndexCrudTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Tenant $otherTenant;

    protected Center $center;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->tenant = Tenant::factory()->create(['status' => 'active']);
        $this->otherTenant = Tenant::factory()->create(['status' => 'active']);

        $this->center = Center::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'المركز الرئيسي',
            'center_type' => 'main',
            'is_main' => true,
            'is_active' => true,
        ]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);

        Permission::firstOrCreate(['name' => Permissions::CENTERS_VIEW, 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => Permissions::CENTERS_MANAGE, 'guard_name' => 'web']);

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
        ]);
        $this->user->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);
        $this->user->givePermissionTo([Permissions::CENTERS_VIEW, Permissions::CENTERS_MANAGE]);
    }

    public function test_unauthorized_user_cannot_view_branches(): void
    {
        $unauthorized = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
        ]);
        $unauthorized->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($unauthorized)->get(route('settings.branches'));

        $response->assertForbidden();
    }

    public function test_authorized_user_can_view_branches_index_and_tenant_isolation(): void
    {
        Center::factory()->create([
            'tenant_id' => $this->otherTenant->id,
            'name' => 'فرع مستأجر آخر',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->get(route('settings.branches'));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Settings/Branches/Index')
                ->has('branches', 1)
                ->where('branches.0.name', 'المركز الرئيسي')
            );
    }

    public function test_authorized_user_can_create_branch_within_plan_limit(): void
    {
        $plan = Plan::create([
            'name' => 'Pro Plan',
            'name_ar' => 'الباقة الاحترافية',
            'name_en' => 'Pro Plan',
            'slug' => 'pro-plan',
            'price_monthly' => 500,
            'price_yearly' => 5000,
            'limits' => ['max_centers' => 3],
            'is_active' => true,
        ]);

        Subscription::create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'price' => 500,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('settings.branches.store'), [
                'name_ar' => 'فرع جدة',
                'name_en' => 'Jeddah Branch',
                'center_type' => 'branch',
                'manager_name' => 'أحمد علي',
                'phone' => '0551112222',
                'email' => 'jeddah@example.com',
                'is_active' => true,
            ]);

        $response->assertRedirect(route('settings.branches'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('centers', [
            'tenant_id' => $this->tenant->id,
            'name' => 'فرع جدة',
            'center_type' => 'branch',
            'manager_name' => 'أحمد علي',
        ]);
    }

    #[DataProvider('planLimitCases')]
    public function test_branch_creation_reads_limits_from_the_eligible_plan(array $limits, bool $expired, bool $allowed): void
    {
        $plan = Plan::where('slug', 'pro')->firstOrFail();
        $plan->update(['limits' => $limits]);
        Subscription::create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'starts_at' => now()->subMonth(),
            'ends_at' => $expired ? now()->subDay() : now()->addMonth(),
            'price' => 100,
        ]);

        $this->actingAs($this->user)->post(route('settings.branches.store'), [
            'name_en' => 'Plan limit test', 'center_type' => 'branch',
        ])->assertRedirect()->assertSessionHas($allowed ? 'success' : 'error');
        $this->assertSame($allowed ? 2 : 1, $this->tenant->centers()->count());
    }

    public static function planLimitCases(): array
    {
        return [
            'canonical key takes precedence' => [['max_centers' => 1, 'centers' => 5], false, false],
            'legacy key' => [['centers' => 2], false, true],
            'unlimited plan' => [['max_centers' => 999], false, true],
            'expired plan ignored' => [['max_centers' => 5], true, false],
            'missing key' => [[], false, false],
        ];
    }

    public function test_cannot_create_branch_beyond_plan_limit(): void
    {
        // Default limit when no subscription is 1 (already 1 center created in setUp)
        $response = $this->actingAs($this->user)
            ->from(route('settings.branches'))
            ->post(route('settings.branches.store'), [
                'name_ar' => 'فرع الدمام',
                'name_en' => 'Dammam Branch',
                'center_type' => 'branch',
                'is_active' => true,
            ]);

        $response->assertRedirect(route('settings.branches'))
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('centers', [
            'tenant_id' => $this->tenant->id,
            'name' => 'فرع الدمام',
        ]);
    }
}
