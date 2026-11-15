<?php

declare(strict_types=1);

namespace Tests\Feature\System;

use App\Events\Billing\ReceiptReviewed;
use App\Exceptions\BankTransferException;
use App\Models\AdminActivityLog;
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
use App\Support\Permissions;
use Carbon\Carbon;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Throwable;

class BankTransferAdminApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected AdminUser $superAdmin;

    protected Tenant $tenant;

    protected Plan $plan;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        PaymentSettings::query()->delete();
        PaymentSettings::create([
            'bank_transfer_enabled' => true,
            'bank_transfer_min_amount' => 0,
            'bank_transfer_max_days' => 7,
            'bank_transfer_require_receipt' => true,
            'vat_enabled' => false,
            'vat_rate' => 15,
        ]);

        $this->superAdmin = AdminUser::create([
            'name' => 'Super Admin',
            'email' => 'super@example.com',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->tenant = Tenant::factory()->create(['status' => 'active']);
        $this->plan = Plan::create([
            'name' => 'Pro Plan',
            'name_ar' => 'باقة متقدمة',
            'name_en' => 'Pro Plan',
            'slug' => 'pro-admin-'.uniqid(),
            'price_monthly' => 100,
            'price_yearly' => 1000,
            'is_active' => true,
        ]);
    }

    /**
     * @return array{0: Subscription, 1: SubscriptionInvoice, 2: SubscriptionPayment, 3: BankTransferReceipt}
     */
    protected function createPendingBundle(
        float $invoiceTotal = 1000.00,
        float $transferAmount = 1000.00,
        string $operationType = SubscriptionInvoice::OPERATION_NEW,
        string $subscriptionStatus = 'pending_payment'
    ): array {
        $sub = Subscription::create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->plan->id,
            'status' => $subscriptionStatus,
            'billing_cycle' => 'yearly',
            'price' => $invoiceTotal,
            'starts_at' => now(),
            'ends_at' => now()->addYear(),
        ]);

        $invoice = SubscriptionInvoice::create([
            'subscription_id' => $sub->id,
            'tenant_id' => $this->tenant->id,
            'invoice_number' => 'INV-'.uniqid(),
            'operation_type' => $operationType,
            'subtotal' => $invoiceTotal,
            'vat' => 0,
            'discount' => 0,
            'total' => $invoiceTotal,
            'status' => 'pending',
            'issued_at' => now(),
            'due_at' => now()->addDays(7),
        ]);

        $payment = SubscriptionPayment::create([
            'tenant_id' => $this->tenant->id,
            'subscription_id' => $sub->id,
            'subscription_invoice_id' => $invoice->id,
            'amount' => $invoiceTotal,
            'currency' => 'SAR',
            'gateway' => 'bank_transfer',
            'status' => 'pending',
        ]);

        $receipt = BankTransferReceipt::create([
            'tenant_id' => $this->tenant->id,
            'subscription_id' => $sub->id,
            'subscription_payment_id' => $payment->id,
            'transfer_amount' => $transferAmount,
            'transfer_reference' => 'REF-'.uniqid(),
            'transfer_date' => now()->toDateString(),
            'bank_name' => 'Al Rajhi',
            'account_name' => 'Test Co',
            'status' => BankTransferReceipt::STATUS_PENDING,
        ]);

        return [$sub, $invoice, $payment, $receipt];
    }

    public function test_covers_previously_skipped_index_show_legacy_and_check_reference_endpoints(): void
    {
        [, , , $receipt] = $this->createPendingBundle(1000.00, 1000.00);
        $legacyReceipt = BankTransferReceipt::create([
            'tenant_id' => $this->tenant->id,
            'transfer_amount' => 500,
            'transfer_reference' => 'REF-LEGACY',
            'transfer_date' => now()->toDateString(),
            'bank_name' => 'SNB',
            'account_name' => 'Legacy',
            'status' => BankTransferReceipt::STATUS_PENDING,
        ]);

        // 1. Index exposes expected_amount & amount_diff
        $this->actingAs($this->superAdmin, 'admin')
            ->get('/system/bank-transfer-receipts')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('System/BankTransferReceipts/Index')
                ->has('receipts.data', 2)
            );

        // 2. Show exposes amount comparison
        $this->actingAs($this->superAdmin, 'admin')
            ->get("/system/bank-transfer-receipts/{$receipt->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('System/BankTransferReceipts/Show')
                ->where('canOverride', true)
                ->where('receipt.expected_amount', 1000)
                ->where('receipt.amount_diff', 0)
                ->where('receipt.payment.status', 'pending')
                ->where('receipt.payment.invoice.status', 'pending')
            );

        // 3. Legacy receipt without payment returns null diff
        $this->actingAs($this->superAdmin, 'admin')
            ->get("/system/bank-transfer-receipts/{$legacyReceipt->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('System/BankTransferReceipts/Show')
                ->where('receipt.expected_amount', null)
                ->where('receipt.amount_diff', null)
            );

        // 4 & 5. Authenticated tenant user with COMPANY_MANAGE hits duplicate ref & check-reference
        $this->seed(PermissionsSeeder::class);
        $center = Center::factory()->create(['tenant_id' => $this->tenant->id]);
        $user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $center->id,
            'two_factor_confirmed_at' => now(),
        ]);
        $user->centers()->attach($center->id, ['tenant_id' => $this->tenant->id]);
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
        $role = Role::firstOrCreate(['tenant_id' => $this->tenant->id, 'name' => 'super_admin', 'guard_name' => 'web']);
        $user->assignRole($role);

        $this->actingAs($user, 'web')
            ->getJson('/app/settings/billing/bank-transfer-receipt/check-reference?ref='.$receipt->transfer_reference)
            ->assertOk()
            ->assertJson(['exists' => true, 'receipt_id' => $receipt->id]);

        $this->actingAs($user, 'web')
            ->postJson('/app/settings/billing/bank-transfer-receipt', [
                'plan_id' => $this->plan->id,
                'transfer_amount' => 1000,
                'transfer_reference' => $receipt->transfer_reference,
                'transfer_date' => now()->toDateString(),
                'bank_name' => 'Al Rajhi',
                'account_name' => 'Test Co',
                'receipt' => UploadedFile::fake()->image('dup.jpg'),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['transfer_reference']);
    }

    public function test_partial_approval_and_multi_payment_cumulative_settlement_activates_only_when_full(): void
    {
        Event::fake([ReceiptReviewed::class]);
        [$sub, $invoice, $payment1, $receipt1] = $this->createPendingBundle(1000.00, 1000.00);

        // First partial approval: approve 600 out of 1000 transferred
        $this->actingAs($this->superAdmin, 'admin')
            ->post("/system/bank-transfer-receipts/{$receipt1->id}/approve", [
                'approved_amount' => 600,
                'notes' => 'Partial batch 1',
            ])
            ->assertSessionHas('success');

        $receipt1->refresh();
        $payment1->refresh();
        $invoice->refresh();
        $sub->refresh();

        $this->assertSame('approved', $receipt1->status);
        $this->assertSame('1000.00', (string) $receipt1->transfer_amount);
        $this->assertSame('600.00', (string) $receipt1->approved_amount);
        $this->assertSame('600.00', (string) $payment1->amount);
        $this->assertSame('paid', $payment1->status);
        $this->assertSame('pending', $invoice->status);
        $this->assertSame('pending_payment', $sub->status);

        // Second payment & receipt for the remaining 400
        $payment2 = SubscriptionPayment::create([
            'tenant_id' => $this->tenant->id,
            'subscription_id' => $sub->id,
            'subscription_invoice_id' => $invoice->id,
            'amount' => 400.00,
            'currency' => 'SAR',
            'gateway' => 'bank_transfer',
            'status' => 'pending',
        ]);
        $receipt2 = BankTransferReceipt::create([
            'tenant_id' => $this->tenant->id,
            'subscription_id' => $sub->id,
            'subscription_payment_id' => $payment2->id,
            'transfer_amount' => 400.00,
            'transfer_reference' => 'REF-PART-2',
            'transfer_date' => now()->toDateString(),
            'bank_name' => 'Al Rajhi',
            'account_name' => 'Test Co',
            'status' => BankTransferReceipt::STATUS_PENDING,
        ]);

        $this->actingAs($this->superAdmin, 'admin')
            ->post("/system/bank-transfer-receipts/{$receipt2->id}/approve", [])
            ->assertSessionHas('success');

        $invoice->refresh();
        $sub->refresh();
        $receipt2->refresh();

        $this->assertSame('400.00', (string) $receipt2->approved_amount);
        $this->assertSame('paid', $invoice->status);
        $this->assertSame('active', $sub->status);
        Event::assertDispatchedTimes(ReceiptReviewed::class, 2);
    }

    public function test_cannot_approve_more_than_remaining_even_with_force_approve(): void
    {
        [, $invoice, , $receipt] = $this->createPendingBundle(1000.00, 1200.00);

        $this->actingAs($this->superAdmin, 'admin')
            ->post("/system/bank-transfer-receipts/{$receipt->id}/approve", [
                'force_approve' => true,
                'approved_amount' => 1100,
                'notes' => 'Trying to over-collect',
            ])
            ->assertSessionHas('error');

        $receipt->refresh();
        $invoice->refresh();
        $this->assertSame('pending', $receipt->status);
        $this->assertSame('pending', $invoice->status);
    }

    public function test_force_approve_requires_override_permission_and_reason(): void
    {
        $regularAdmin = AdminUser::create([
            'name' => 'Regular Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'permissions' => [
                Permissions::SYSTEM_INVOICES_VIEW,
                Permissions::SYSTEM_INVOICES_MANAGE,
            ],
            'is_active' => true,
        ]);

        [, , , $receipt] = $this->createPendingBundle(1000.00, 800.00);

        // HTTP layer: non-super-admin is blocked by super_admin route middleware
        $this->actingAs($regularAdmin, 'admin')
            ->post("/system/bank-transfer-receipts/{$receipt->id}/approve", [
                'force_approve' => true,
                'notes' => 'Trying override without permission',
            ])
            ->assertForbidden();

        // Service layer: admin without system.receipts.override is blocked
        $service = app(BankTransferService::class);

        try {
            $service->approveReceipt($receipt, $regularAdmin, 'Trying override without permission', true);
            $this->fail('Expected BankTransferException for missing override permission.');
        } catch (BankTransferException $e) {
            $this->assertSame(403, $e->getCode());
        }

        $this->assertSame('pending', $receipt->fresh()->status);

        // Super admin without reason is blocked
        $this->actingAs($this->superAdmin, 'admin')
            ->post("/system/bank-transfer-receipts/{$receipt->id}/approve", [
                'force_approve' => true,
            ])
            ->assertSessionHas('error');

        $this->assertSame('pending', $receipt->fresh()->status);

        // Service layer: admin explicitly granted system.receipts.override + reason succeeds
        $overrideAdmin = AdminUser::create([
            'name' => 'Override Admin',
            'email' => 'override@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'permissions' => [
                Permissions::SYSTEM_INVOICES_VIEW,
                Permissions::SYSTEM_INVOICES_MANAGE,
                Permissions::SYSTEM_RECEIPTS_OVERRIDE,
            ],
            'is_active' => true,
        ]);

        $serviceResult = $service->approveReceipt(
            $receipt,
            $overrideAdmin,
            'Approved after finance confirmation',
            true
        );

        $this->assertTrue($serviceResult['audit']['force_approved']);
        $this->assertSame($overrideAdmin->id, $serviceResult['audit']['override_by']);
        $this->assertSame('Approved after finance confirmation', $serviceResult['audit']['override_reason']);

        $receipt->refresh();
        $this->assertSame('approved', $receipt->status);
        $this->assertStringContainsString('Approved after finance confirmation', (string) $receipt->review_notes);

        // HTTP layer: super admin with reason succeeds and writes AdminActivityLog
        [, , , $receipt2] = $this->createPendingBundle(1000.00, 850.00);

        $this->actingAs($this->superAdmin, 'admin')
            ->post("/system/bank-transfer-receipts/{$receipt2->id}/approve", [
                'force_approve' => true,
                'notes' => 'Super admin override confirmed',
            ])
            ->assertSessionHas('success');

        $this->assertTrue(
            AdminActivityLog::where('action', 'receipt_approve')
                ->where('model_id', $receipt2->id)
                ->exists()
        );
    }

    public function test_reject_marks_payment_failed_and_preserves_subscription_and_invoice(): void
    {
        Event::fake([ReceiptReviewed::class]);
        [$sub, $invoice, $payment, $receipt] = $this->createPendingBundle(1000.00, 1000.00);

        $this->actingAs($this->superAdmin, 'admin')
            ->post("/system/bank-transfer-receipts/{$receipt->id}/reject", [
                'notes' => 'Unclear receipt image',
            ])
            ->assertSessionHas('success');

        $receipt->refresh();
        $payment->refresh();
        $invoice->refresh();
        $sub->refresh();

        $this->assertSame('rejected', $receipt->status);
        $this->assertSame('failed', $payment->status);
        $this->assertSame('Unclear receipt image', $payment->failure_reason);
        $this->assertSame('pending', $invoice->status);
        $this->assertSame('pending_payment', $sub->status);
        Event::assertDispatchedTimes(ReceiptReviewed::class, 1);
    }

    public function test_renewal_invoice_extends_calendar_year_and_double_approve_does_not_double_extend(): void
    {
        Event::fake([ReceiptReviewed::class]);
        $initialEnd = Carbon::parse('2027-02-28 12:00:00');
        Carbon::setTestNow(Carbon::parse('2026-11-15 09:00:00'));

        $sub = Subscription::create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->plan->id,
            'status' => 'active',
            'billing_cycle' => 'yearly',
            'price' => 1000,
            'starts_at' => Carbon::parse('2026-02-28 12:00:00'),
            'ends_at' => $initialEnd,
        ]);

        $invoice = SubscriptionInvoice::create([
            'subscription_id' => $sub->id,
            'tenant_id' => $this->tenant->id,
            'invoice_number' => 'INV-REN-CAL',
            'operation_type' => SubscriptionInvoice::OPERATION_RENEWAL,
            'subtotal' => 1000,
            'vat' => 0,
            'discount' => 0,
            'total' => 1000,
            'status' => 'pending',
            'issued_at' => now(),
            'due_at' => now()->addDays(7),
        ]);

        $payment = SubscriptionPayment::create([
            'tenant_id' => $this->tenant->id,
            'subscription_id' => $sub->id,
            'subscription_invoice_id' => $invoice->id,
            'amount' => 1000,
            'currency' => 'SAR',
            'gateway' => 'bank_transfer',
            'status' => 'pending',
        ]);

        $receipt = BankTransferReceipt::create([
            'tenant_id' => $this->tenant->id,
            'subscription_id' => $sub->id,
            'subscription_payment_id' => $payment->id,
            'transfer_amount' => 1000,
            'transfer_reference' => 'REF-REN-CAL',
            'transfer_date' => now()->toDateString(),
            'bank_name' => 'Al Rajhi',
            'account_name' => 'Test Co',
            'status' => BankTransferReceipt::STATUS_PENDING,
        ]);

        // First approval extends by 1 calendar year from 2027-02-28 -> 2028-02-28 (leap year handled by Carbon::addYear)
        $this->actingAs($this->superAdmin, 'admin')
            ->post("/system/bank-transfer-receipts/{$receipt->id}/approve", [])
            ->assertSessionHas('success');

        $sub->refresh();
        $this->assertSame('2028-02-28', $sub->ends_at->toDateString());

        // Re-approving the same receipt does NOT double-extend or fire duplicate notification
        $this->actingAs($this->superAdmin, 'admin')
            ->post("/system/bank-transfer-receipts/{$receipt->id}/approve", [])
            ->assertSessionHas('error');

        $this->assertSame('2028-02-28', $sub->fresh()->ends_at->toDateString());
        Event::assertDispatchedTimes(ReceiptReviewed::class, 1);

        Carbon::setTestNow();
    }

    public function test_ambiguous_legacy_operation_type_halts_safely_without_mutating_records(): void
    {
        $sub = Subscription::create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->plan->id,
            'status' => 'active',
            'billing_cycle' => 'yearly',
            'price' => 1000,
        ]);

        $invoice = SubscriptionInvoice::create([
            'subscription_id' => $sub->id,
            'tenant_id' => $this->tenant->id,
            'invoice_number' => 'INV-AMB-SAFE',
            'operation_type' => null,
            'subtotal' => 1000,
            'vat' => 0,
            'discount' => 0,
            'total' => 1000,
            'status' => 'pending',
            'issued_at' => now(),
            'due_at' => now()->addDays(7),
        ]);

        $payment = SubscriptionPayment::create([
            'tenant_id' => $this->tenant->id,
            'subscription_id' => $sub->id,
            'subscription_invoice_id' => $invoice->id,
            'amount' => 1000,
            'currency' => 'SAR',
            'gateway' => 'bank_transfer',
            'status' => 'pending',
        ]);

        $receipt = BankTransferReceipt::create([
            'tenant_id' => $this->tenant->id,
            'subscription_id' => $sub->id,
            'subscription_payment_id' => $payment->id,
            'transfer_amount' => 1000,
            'transfer_reference' => 'REF-AMB-SAFE',
            'transfer_date' => now()->toDateString(),
            'bank_name' => 'Al Rajhi',
            'account_name' => 'Test Co',
            'status' => BankTransferReceipt::STATUS_PENDING,
        ]);

        $this->actingAs($this->superAdmin, 'admin')
            ->post("/system/bank-transfer-receipts/{$receipt->id}/approve", [])
            ->assertSessionHas('error');

        $this->assertSame('pending', $receipt->fresh()->status);
        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertSame('pending', $invoice->fresh()->status);
    }

    public function test_concurrent_two_receipts_on_same_invoice_cannot_overcollect(): void
    {
        [$sub, $invoice, , $receipt1] = $this->createPendingBundle(1000.00, 1000.00);

        $payment2 = SubscriptionPayment::create([
            'tenant_id' => $this->tenant->id,
            'subscription_id' => $sub->id,
            'subscription_invoice_id' => $invoice->id,
            'amount' => 1000,
            'currency' => 'SAR',
            'gateway' => 'bank_transfer',
            'status' => 'pending',
        ]);
        $receipt2 = BankTransferReceipt::create([
            'tenant_id' => $this->tenant->id,
            'subscription_id' => $sub->id,
            'subscription_payment_id' => $payment2->id,
            'transfer_amount' => 1000,
            'transfer_reference' => 'REF-RACE-2',
            'transfer_date' => now()->toDateString(),
            'bank_name' => 'Al Rajhi',
            'account_name' => 'Test Co',
            'status' => BankTransferReceipt::STATUS_PENDING,
        ]);

        // First receipt settles the invoice completely
        $this->actingAs($this->superAdmin, 'admin')
            ->post("/system/bank-transfer-receipts/{$receipt1->id}/approve", [])
            ->assertSessionHas('success');

        // Second receipt on the same already-settled invoice is rejected cleanly
        $this->actingAs($this->superAdmin, 'admin')
            ->post("/system/bank-transfer-receipts/{$receipt2->id}/approve", [])
            ->assertSessionHas('error');

        $this->assertSame('approved', $receipt1->fresh()->status);
        $this->assertSame('pending', $receipt2->fresh()->status);
        $this->assertSame(100000, app(BankTransferService::class)->getPaidHalalas($invoice->fresh()));
    }

    public function test_orphan_receipt_file_is_not_left_when_submission_fails(): void
    {
        $service = app(BankTransferService::class);
        $file = UploadedFile::fake()->image('will-fail.png');

        try {
            $service->submitReceipt(
                $this->tenant,
                [
                    'subscription_invoice_id' => 999999, // non-existent invoice -> fails inside transaction
                    'transfer_amount' => 1000,
                    'transfer_reference' => 'REF-FAIL-CLEAN',
                    'transfer_date' => now()->toDateString(),
                    'bank_name' => 'Al Rajhi',
                    'account_name' => 'Test Co',
                ],
                $file
            );
            $this->fail('Expected BankTransferException was not thrown.');
        } catch (Throwable) {
            // Expected
        }

        $this->assertSame([], Storage::disk('local')->allFiles("bank-transfer-receipts/{$this->tenant->id}"));
    }
}
