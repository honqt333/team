<?php

declare(strict_types=1);

namespace Tests\Feature\App;

use App\Models\AdminUser;
use App\Models\BankTransferReceipt;
use App\Models\Billing\Plan;
use App\Models\Billing\Subscription;
use App\Models\Billing\SubscriptionInvoice;
use App\Models\Billing\SubscriptionPayment;
use App\Models\Center;
use App\Models\PaymentSettings;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Billing\BankTransferService;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class BillingCancellationAuthorizationTest extends TestCase
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

    public function test_under_review_pending_subscription_cannot_be_cancelled_via_cancel_endpoint(): void
    {
        [$tenant, $user] = $this->createTenantUser();
        $plan = $this->createPlan(1000.00);

        $res = $this->actingAs($user)
            ->postJson('/app/settings/billing/bank-transfer-receipt', [
                'plan_id' => $plan->id,
                'transfer_amount' => 1000,
                'transfer_reference' => 'REF-UR-1',
                'transfer_date' => now()->toDateString(),
                'bank_name' => 'Al Rajhi',
                'account_name' => 'Workshop',
                'receipt' => UploadedFile::fake()->image('receipt.png'),
            ])
            ->assertOk();

        $subId = $res->json('subscription.id');
        $invId = $res->json('invoice.id');
        $receiptId = $res->json('receipt.id');

        $this->actingAs($user)
            ->postJson(route('app.billing.cancel', $subId))
            ->assertStatus(409);

        $sub = Subscription::findOrFail($subId);
        $inv = SubscriptionInvoice::findOrFail($invId);
        $receipt = BankTransferReceipt::findOrFail($receiptId);

        $this->assertContains($sub->status, ['pending', 'pending_payment']);
        $this->assertContains($inv->status, ['pending', 'pending_payment']);
        $this->assertSame(BankTransferReceipt::STATUS_PENDING, $receipt->status);
    }

    public function test_partially_paid_subscription_cannot_be_cancelled_via_cancel_or_cancel_pending(): void
    {
        [$tenant, $user] = $this->createTenantUser();
        $admin = $this->createAdmin();
        $plan = $this->createPlan(1000.00);
        $service = app(BankTransferService::class);

        $res = $this->actingAs($user)
            ->postJson('/app/settings/billing/bank-transfer-receipt', [
                'plan_id' => $plan->id,
                'transfer_amount' => 400,
                'transfer_reference' => 'REF-PP-1',
                'transfer_date' => now()->toDateString(),
                'bank_name' => 'Al Rajhi',
                'account_name' => 'Workshop',
                'receipt' => UploadedFile::fake()->image('part.png'),
            ])
            ->assertOk();

        $subId = $res->json('subscription.id');
        $invId = $res->json('invoice.id');
        $paymentId = $res->json('payment.id');
        $receiptId = $res->json('receipt.id');

        $service->approveReceipt(BankTransferReceipt::findOrFail($receiptId), $admin, 'Partial 400', true, 400.00);

        $this->actingAs($user)
            ->postJson(route('app.billing.cancel', $subId))
            ->assertStatus(409);

        $this->actingAs($user)
            ->postJson(route('app.billing.cancel-pending'))
            ->assertStatus(409);

        $this->assertSame(40000, $service->getPaidHalalas(SubscriptionInvoice::findOrFail($invId)));
        $this->assertSame('paid', SubscriptionPayment::findOrFail($paymentId)->status);
        $this->assertSame('REF-PP-1', BankTransferReceipt::findOrFail($receiptId)->transfer_reference);
        $this->assertSame('partially_paid', $service->getTenantBillingState($tenant)['display_status']);
    }

    public function test_unpaid_pending_subscription_with_rejected_receipt_can_be_cancelled_and_preserves_history(): void
    {
        [$tenant, $user] = $this->createTenantUser();
        $admin = $this->createAdmin();
        $plan = $this->createPlan(1000.00);
        $service = app(BankTransferService::class);

        $res = $this->actingAs($user)
            ->postJson('/app/settings/billing/bank-transfer-receipt', [
                'plan_id' => $plan->id,
                'transfer_amount' => 1000,
                'transfer_reference' => 'REF-REJ-CAN-1',
                'transfer_date' => now()->toDateString(),
                'bank_name' => 'Al Rajhi',
                'account_name' => 'Workshop',
                'receipt' => UploadedFile::fake()->image('rej.png'),
            ])
            ->assertOk();

        $subId = $res->json('subscription.id');
        $invId = $res->json('invoice.id');
        $receiptId = $res->json('receipt.id');

        $service->rejectReceipt(BankTransferReceipt::findOrFail($receiptId), $admin, 'Invalid receipt');

        $this->actingAs($user)
            ->postJson(route('app.billing.cancel', $subId))
            ->assertOk();

        $this->assertSame('cancelled', Subscription::findOrFail($subId)->status);
        $this->assertSame('cancelled', SubscriptionInvoice::findOrFail($invId)->status);
        $this->assertNotNull(BankTransferReceipt::find($receiptId));
    }

    public function test_active_subscription_can_still_be_cancelled_preserving_current_policy(): void
    {
        [$tenant, $user] = $this->createTenantUser();
        $plan = $this->createPlan(1000.00);

        $activeSub = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => 'yearly',
            'price' => 1000,
            'currency' => 'SAR',
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->addMonths(11),
            'auto_renew' => true,
        ]);

        $this->actingAs($user)
            ->post(route('app.billing.cancel', $activeSub->id))
            ->assertRedirect();

        $fresh = $activeSub->fresh();
        $this->assertSame('active', $fresh->status);
        $this->assertFalse((bool) $fresh->auto_renew);
        $this->assertNotNull($fresh->cancelled_at);
    }

    public function test_unauthorized_and_cross_tenant_cancel_requests_are_rejected_without_mutation(): void
    {
        [$tenantA, $userWithoutPerm] = $this->createTenantUser(false);
        [$tenantB, $userB] = $this->createTenantUser(true);
        $plan = $this->createPlan(1000.00);

        $subA = Subscription::create([
            'tenant_id' => $tenantA->id,
            'plan_id' => $plan->id,
            'status' => 'pending_payment',
            'billing_cycle' => 'yearly',
            'price' => 1000,
            'currency' => 'SAR',
        ]);

        $subB = Subscription::create([
            'tenant_id' => $tenantB->id,
            'plan_id' => $plan->id,
            'status' => 'pending_payment',
            'billing_cycle' => 'yearly',
            'price' => 1000,
            'currency' => 'SAR',
        ]);

        $this->actingAs($userWithoutPerm)
            ->postJson(route('app.billing.cancel', $subA->id))
            ->assertForbidden();

        $this->actingAs($userWithoutPerm)
            ->postJson(route('app.billing.cancel-pending'))
            ->assertForbidden();

        $this->actingAs($userB)
            ->postJson(route('app.billing.cancel', $subA->id))
            ->assertNotFound();

        $this->assertSame('pending_payment', $subA->fresh()->status);
        $this->assertSame('pending_payment', $subB->fresh()->status);
    }
}
