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
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class BankTransferRejectionLifecycleTest extends TestCase
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

    private function createTenantUser(): array
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
        $superAdminRole = Role::firstOrCreate([
            'tenant_id' => $tenant->id,
            'name' => 'super_admin',
            'guard_name' => 'web',
        ]);
        $user->assignRole($superAdminRole);

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

    public function test_billing_state_distinguishes_under_review_rejected_and_partially_paid(): void
    {
        [$tenant, $user] = $this->createTenantUser();
        $admin = $this->createAdmin();
        $plan = $this->createPlan(1000.00);
        $service = app(BankTransferService::class);

        $upload = $this->actingAs($user)
            ->postJson('/app/settings/billing/bank-transfer-receipt', [
                'plan_id' => $plan->id,
                'transfer_amount' => 1000,
                'transfer_reference' => 'REF-STATE-1',
                'transfer_date' => now()->toDateString(),
                'bank_name' => 'Al Rajhi',
                'account_name' => 'Workshop',
                'receipt' => UploadedFile::fake()->image('r1.png'),
            ])
            ->assertOk();

        $receipt1 = BankTransferReceipt::findOrFail($upload->json('receipt.id'));

        // 1. Under review while receipt is pending
        $this->actingAs($user)
            ->get('/app/settings/billing')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('billing_state.display_status', 'under_review')
                ->where('billing_state.has_pending_receipt', true)
                ->where('billing_state.allowed_actions.can_upload_receipt', false)
                ->where('billing_state.allowed_actions.can_cancel_pending', false)
                ->where('billing_state.allowed_actions.can_change_plan_or_gateway', false)
            );

        // 2. Reject -> display_status becomes receipt_rejected and actions unlock
        $service->rejectReceipt($receipt1, $admin, 'Unclear image');

        $this->actingAs($user)
            ->get('/app/settings/billing')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('billing_state.display_status', 'receipt_rejected')
                ->where('billing_state.has_pending_receipt', false)
                ->where('billing_state.has_rejected_receipt', true)
                ->where('billing_state.latest_rejected_receipt.review_notes', 'Unclear image')
                ->where('billing_state.allowed_actions.can_upload_receipt', true)
                ->where('billing_state.allowed_actions.can_cancel_pending', true)
                ->where('billing_state.allowed_actions.can_change_plan_or_gateway', true)
            );

        // 3. Re-upload partial amount & approve -> partially_paid blocks cancel & plan change
        $upload2 = $this->actingAs($user)
            ->postJson('/app/settings/billing/bank-transfer-receipt', [
                'plan_id' => $plan->id,
                'subscription_invoice_id' => $upload->json('invoice.id'),
                'transfer_amount' => 600,
                'transfer_reference' => 'REF-STATE-2',
                'transfer_date' => now()->toDateString(),
                'bank_name' => 'Al Rajhi',
                'account_name' => 'Workshop',
                'receipt' => UploadedFile::fake()->image('r2.png'),
            ])
            ->assertOk();

        $receipt2 = BankTransferReceipt::findOrFail($upload2->json('receipt.id'));
        $service->approveReceipt($receipt2, $admin, 'Partial 600', true, 600.00);

        $invoiceTotal = (float) $upload->json('invoice.total');
        $expectedRemaining = round($invoiceTotal - 600, 2);

        $this->actingAs($user)
            ->get('/app/settings/billing')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('billing_state.display_status', 'partially_paid')
                ->where('billing_state.has_approved_payments', true)
                ->where('billing_state.open_invoice.paid_amount', 600)
                ->where('billing_state.open_invoice.remaining_amount', fn ($val): bool => abs((float) $val - $expectedRemaining) < 0.001)
                ->where('billing_state.allowed_actions.can_upload_receipt', true)
                ->where('billing_state.allowed_actions.can_cancel_pending', false)
                ->where('billing_state.allowed_actions.can_change_plan_or_gateway', false)
            );
    }

    public function test_rejected_receipt_allows_resubmit_and_approval_preserves_history(): void
    {
        [$tenant, $user] = $this->createTenantUser();
        $admin = $this->createAdmin();
        $plan = $this->createPlan(1000.00);
        $service = app(BankTransferService::class);

        $first = $this->actingAs($user)
            ->postJson('/app/settings/billing/bank-transfer-receipt', [
                'plan_id' => $plan->id,
                'transfer_amount' => 1150,
                'transfer_reference' => 'REF-REJ-1',
                'transfer_date' => now()->toDateString(),
                'bank_name' => 'Al Rajhi',
                'account_name' => 'Workshop',
                'receipt' => UploadedFile::fake()->image('bad.png'),
            ])
            ->assertOk();

        $subId = $first->json('subscription.id');
        $invId = $first->json('invoice.id');
        $invoiceTotal = (float) $first->json('invoice.total');
        $pay1Id = $first->json('payment.id');
        $rec1Id = $first->json('receipt.id');

        $service->rejectReceipt(BankTransferReceipt::findOrFail($rec1Id), $admin, 'Wrong date');

        $second = $this->actingAs($user)
            ->postJson('/app/settings/billing/bank-transfer-receipt', [
                'plan_id' => $plan->id,
                'subscription_invoice_id' => $invId,
                'transfer_amount' => $invoiceTotal,
                'transfer_reference' => 'REF-REJ-2',
                'transfer_date' => now()->toDateString(),
                'bank_name' => 'Al Rajhi',
                'account_name' => 'Workshop',
                'receipt' => UploadedFile::fake()->image('good.png'),
            ])
            ->assertOk();

        $this->assertSame($subId, $second->json('subscription.id'));
        $this->assertSame($invId, $second->json('invoice.id'));
        $this->assertSame('rejected', BankTransferReceipt::findOrFail($rec1Id)->status);
        $this->assertSame('failed', SubscriptionPayment::findOrFail($pay1Id)->status);

        $service->approveReceipt(BankTransferReceipt::findOrFail($second->json('receipt.id')), $admin, 'OK', false);

        $this->assertSame('active', Subscription::findOrFail($subId)->status);
        $this->assertSame('paid', SubscriptionInvoice::findOrFail($invId)->status);
        $this->assertSame((int) round($invoiceTotal * 100), $service->getPaidHalalas(SubscriptionInvoice::findOrFail($invId)));
    }

    public function test_cancel_pending_and_plan_change_preserve_history_after_rejection_but_block_when_partially_paid(): void
    {
        [$tenant, $user] = $this->createTenantUser();
        $admin = $this->createAdmin();
        $plan1 = $this->createPlan(1000.00);
        $plan2 = $this->createPlan(2000.00);
        $service = app(BankTransferService::class);

        $first = $this->actingAs($user)
            ->postJson('/app/settings/billing/bank-transfer-receipt', [
                'plan_id' => $plan1->id,
                'transfer_amount' => 1000,
                'transfer_reference' => 'REF-SW-1',
                'transfer_date' => now()->toDateString(),
                'bank_name' => 'Al Rajhi',
                'account_name' => 'Workshop',
                'receipt' => UploadedFile::fake()->image('r1.png'),
            ])
            ->assertOk();

        $rec1 = BankTransferReceipt::findOrFail($first->json('receipt.id'));
        $sub1Id = $first->json('subscription.id');
        $inv1Id = $first->json('invoice.id');
        $pay1Id = $first->json('payment.id');

        // Blocked while receipt is pending
        $this->actingAs($user)->postJson('/app/settings/billing/cancel-pending')->assertStatus(409);

        $service->rejectReceipt($rec1, $admin, 'Rejected');

        // Plan switch succeeds after rejection and preserves historical rows
        $this->actingAs($user)
            ->postJson('/app/settings/billing/subscribe', [
                'plan_id' => $plan2->id,
                'billing_cycle' => 'yearly',
                'gateway' => 'bank_transfer',
            ])
            ->assertOk()
            ->assertJson(['bank_transfer_setup' => true]);

        $this->assertSame('cancelled', Subscription::findOrFail($sub1Id)->status);
        $this->assertSame('cancelled', SubscriptionInvoice::findOrFail($inv1Id)->status);
        $this->assertSame('rejected', BankTransferReceipt::findOrFail($rec1->id)->status);
        $this->assertSame('failed', SubscriptionPayment::findOrFail($pay1Id)->status);

        // New partial payment on plan2 blocks cancel-pending and plan change
        $partialUpload = $this->actingAs($user)
            ->postJson('/app/settings/billing/bank-transfer-receipt', [
                'plan_id' => $plan2->id,
                'transfer_amount' => 500,
                'transfer_reference' => 'REF-PARTIAL-BLOCK',
                'transfer_date' => now()->toDateString(),
                'bank_name' => 'Al Rajhi',
                'account_name' => 'Workshop',
                'receipt' => UploadedFile::fake()->image('p1.png'),
            ])
            ->assertOk();

        $service->approveReceipt(
            BankTransferReceipt::findOrFail($partialUpload->json('receipt.id')),
            $admin,
            'Partial 500',
            true,
            500.00
        );

        $this->actingAs($user)->postJson('/app/settings/billing/cancel-pending')->assertStatus(409);
        $this->actingAs($user)
            ->postJson('/app/settings/billing/subscribe', [
                'plan_id' => $plan1->id,
                'billing_cycle' => 'yearly',
                'gateway' => 'bank_transfer',
            ])
            ->assertStatus(409);
    }

    public function test_tenant_isolation_prevents_cross_tenant_billing_state_or_invoice_access(): void
    {
        [$tenantA, $userA] = $this->createTenantUser();
        [$tenantB, $userB] = $this->createTenantUser();
        $plan = $this->createPlan(1000.00);

        $uploadA = $this->actingAs($userA)
            ->postJson('/app/settings/billing/bank-transfer-receipt', [
                'plan_id' => $plan->id,
                'transfer_amount' => 1000,
                'transfer_reference' => 'REF-ISO-A',
                'transfer_date' => now()->toDateString(),
                'bank_name' => 'Al Rajhi',
                'account_name' => 'Workshop A',
                'receipt' => UploadedFile::fake()->image('a.png'),
            ])
            ->assertOk();

        $this->actingAs($userB)
            ->get('/app/settings/billing')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('billing_state.display_status', 'none')
                ->where('billing_state.open_invoice', null)
            );

        $this->actingAs($userB)
            ->postJson('/app/settings/billing/bank-transfer-receipt', [
                'subscription_invoice_id' => $uploadA->json('invoice.id'),
                'transfer_amount' => 1000,
                'transfer_reference' => 'REF-ISO-B',
                'transfer_date' => now()->toDateString(),
                'bank_name' => 'Al Rajhi',
                'account_name' => 'Workshop B',
                'receipt' => UploadedFile::fake()->image('b.png'),
            ])
            ->assertStatus(422);
    }

    public function test_historical_rejected_receipt_is_ignored_and_multiple_open_invoices_align_cleanly(): void
    {
        [$tenant, $user] = $this->createTenantUser();
        $admin = $this->createAdmin();
        $plan = $this->createPlan(1000.00);
        $service = app(BankTransferService::class);

        $upload1 = $this->actingAs($user)
            ->postJson('/app/settings/billing/bank-transfer-receipt', [
                'plan_id' => $plan->id,
                'transfer_amount' => 1150,
                'transfer_reference' => 'REF-HIST-1',
                'transfer_date' => now()->toDateString(),
                'bank_name' => 'Al Rajhi',
                'account_name' => 'Workshop',
                'receipt' => UploadedFile::fake()->image('hist1.png'),
            ])
            ->assertOk();

        $service->rejectReceipt(BankTransferReceipt::findOrFail($upload1->json('receipt.id')), $admin, 'Old bad receipt');

        // Cancel that subscription -> no open invoices remain, historical rejected receipt MUST NOT pollute state
        $this->actingAs($user)->postJson(route('app.billing.cancel-pending'))->assertOk();

        $stateAfterCancel = $service->getTenantBillingState($tenant);
        $this->assertSame('none', $stateAfterCancel['display_status']);
        $this->assertNull($stateAfterCancel['latest_rejected_receipt']);
        $this->assertFalse($stateAfterCancel['has_rejected_receipt']);

        // Create two open invoices: invoice A (older, unpaid) and invoice B (newer, partially paid)
        $sub = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'pending_payment',
            'billing_cycle' => 'yearly',
            'price' => 1000,
        ]);

        $invA = SubscriptionInvoice::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $sub->id,
            'invoice_number' => 'INV-MULTI-A',
            'subtotal' => 1000,
            'total' => 1000,
            'status' => 'pending',
            'due_at' => now()->addDays(1),
        ]);

        $invB = SubscriptionInvoice::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $sub->id,
            'invoice_number' => 'INV-MULTI-B',
            'subtotal' => 1000,
            'total' => 1000,
            'status' => 'pending',
            'due_at' => now()->addDays(5),
        ]);

        SubscriptionPayment::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $sub->id,
            'subscription_invoice_id' => $invB->id,
            'amount' => 400,
            'currency' => 'SAR',
            'gateway' => 'bank_transfer',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $multiState = $service->getTenantBillingState($tenant);
        $this->assertSame('partially_paid', $multiState['display_status']);
        $this->assertSame(2, $multiState['open_invoices_count']);
        $this->assertTrue($multiState['has_multiple_open_invoices']);
        $this->assertSame($invB->id, $multiState['open_invoice']['id']);
        $this->assertFalse($multiState['allowed_actions']['can_cancel_pending']);
        $this->assertFalse($multiState['allowed_actions']['can_change_plan_or_gateway']);
    }
}
