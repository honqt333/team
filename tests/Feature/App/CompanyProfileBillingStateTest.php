<?php

declare(strict_types=1);

namespace Tests\Feature\App;

use App\Models\AdminUser;
use App\Models\BankTransferReceipt;
use App\Models\Billing\Plan;
use App\Models\Center;
use App\Models\PaymentSettings;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Billing\BankTransferService;
use App\Support\TenancyContext;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CompanyProfileBillingStateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        PaymentSettings::query()->delete();
        PaymentSettings::create([
            'bank_transfer_enabled' => true,
            'bank_transfer_require_receipt' => true,
            'bank_transfer_min_amount' => 100,
            'bank_transfer_max_days' => 7,
            'bank_accounts' => [
                ['bank_name' => 'Al Rajhi', 'account_name' => 'Khidmah Pro', 'iban' => 'SA0000000000000000000000'],
            ],
            'vat_enabled' => false,
            'vat_rate' => 0,
        ]);
    }

    private function createTenantUser(bool $withManagePermission = true): array
    {
        $this->seed(PermissionsSeeder::class);

        $tenant = Tenant::factory()->create();
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
            'two_factor_confirmed_at' => now(),
        ]);
        $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        $roleName = $withManagePermission ? 'super_admin' : 'viewer_only';
        $role = Role::firstOrCreate([
            'tenant_id' => $tenant->id,
            'name' => $roleName,
            'guard_name' => 'web',
        ]);

        if (! $withManagePermission) {
            $role->syncPermissions([]);
        }

        $user->assignRole($role);

        return [$tenant, $user];
    }

    private function createAdmin(): AdminUser
    {
        return AdminUser::create([
            'name' => 'Super Admin',
            'email' => 'admin-'.uniqid().'@example.com',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);
    }

    private function createPlan(float $price = 1000.00): Plan
    {
        return Plan::create([
            'name' => 'Plan '.uniqid(),
            'name_ar' => 'باقة '.uniqid(),
            'name_en' => 'Plan '.uniqid(),
            'slug' => 'plan-'.uniqid(),
            'price_yearly' => $price,
            'currency' => 'SAR',
            'is_active' => true,
        ]);
    }

    public function test_company_profile_index_passes_billing_state_and_isolates_tenants(): void
    {
        [$tenantA, $userA] = $this->createTenantUser(true);
        [$tenantB, $userB] = $this->createTenantUser(true);
        $admin = $this->createAdmin();
        $plan = $this->createPlan(1000.00);
        $service = app(BankTransferService::class);

        // Tenant A has a pending receipt (under_review)
        $res = $this->actingAs($userA)
            ->postJson('/app/settings/billing/bank-transfer-receipt', [
                'plan_id' => $plan->id,
                'transfer_amount' => 400,
                'transfer_reference' => 'REF-COMP-1',
                'transfer_date' => now()->toDateString(),
                'bank_name' => 'Al Rajhi',
                'account_name' => 'Workshop A',
                'receipt' => UploadedFile::fake()->image('r1.png'),
            ])
            ->assertOk();

        $subIdA = $res->json('subscription.id');
        $receiptIdA = $res->json('receipt.id');

        // 1. Under review on Settings/Company/Index
        $this->actingAs($userA)
            ->get(route('settings.company'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Settings/Company/Index')
                ->where('billing_state.display_status', 'under_review')
                ->where('billing_state.pending_subscription_id', $subIdA)
                ->where('billing_state.allowed_actions.can_upload_receipt', false)
                ->where('billing_state.allowed_actions.can_cancel_pending', false)
                ->where('billing_state.allowed_actions.can_change_plan_or_gateway', false)
            );

        // 2. Tenant B stays isolated with display_status = none
        $this->actingAs($userB)
            ->get(route('settings.company'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Settings/Company/Index')
                ->where('billing_state.display_status', 'none')
                ->where('billing_state.pending_subscription_id', null)
            );

        // 3. Partial approval -> partially_paid on Settings/Company/Index
        auth()->logout();
        TenancyContext::clear();
        $receiptA = BankTransferReceipt::withoutGlobalScopes()->findOrFail($receiptIdA);
        $service->approveReceipt($receiptA, $admin, 'Partial', true, 400.00);

        $this->actingAs($userA)
            ->get(route('settings.company'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Settings/Company/Index')
                ->where('billing_state.display_status', 'partially_paid')
                ->where('billing_state.pending_subscription_id', $subIdA)
                ->where('billing_state.allowed_actions.can_upload_receipt', true)
                ->where('billing_state.allowed_actions.can_cancel_pending', false)
                ->where('billing_state.allowed_actions.can_change_plan_or_gateway', false)
            );
    }

    public function test_company_profile_index_denies_access_without_company_manage_permission(): void
    {
        [, $unauthorizedUser] = $this->createTenantUser(false);

        $this->actingAs($unauthorizedUser)
            ->get(route('settings.company'))
            ->assertForbidden();
    }
}
