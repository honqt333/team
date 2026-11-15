<?php

declare(strict_types=1);

namespace Tests\Feature\App;

use App\Exceptions\BankTransferException;
use App\Models\AdminUser;
use App\Models\BankTransferReceipt;
use App\Models\Billing\Plan;
use App\Models\Billing\Subscription;
use App\Models\Billing\SubscriptionInvoice;
use App\Models\Billing\SubscriptionPayment;
use App\Models\PaymentSettings;
use App\Models\Tenant;
use App\Services\Billing\BankTransferService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BankTransferLockOrderRegressionTest extends TestCase
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

    private function makeFixture(bool $withReceipt = true, bool $nullReceiptSubId = false): array
    {
        $tenant = Tenant::factory()->create(['status' => 'pending_payment']);
        $plan = Plan::create([
            'name' => 'Plan '.uniqid(),
            'name_ar' => 'باقة '.uniqid(),
            'name_en' => 'Plan '.uniqid(),
            'slug' => 'plan-'.uniqid(),
            'price_yearly' => 1000,
            'currency' => 'SAR',
            'is_active' => true,
        ]);
        $admin = AdminUser::create([
            'name' => 'Admin',
            'email' => 'admin-'.uniqid().'@example.com',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);
        $sub = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'pending_payment',
            'billing_cycle' => 'yearly',
            'price' => 1000,
            'currency' => 'SAR',
        ]);
        $inv = SubscriptionInvoice::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $sub->id,
            'invoice_number' => 'SUB-LO-'.uniqid(),
            'amount' => 1000,
            'tax_amount' => 0,
            'total' => 1000,
            'currency' => 'SAR',
            'status' => 'pending_payment',
            'due_at' => now()->addDays(7),
        ]);

        $receipt = null;

        if ($withReceipt) {
            $payment = SubscriptionPayment::create([
                'tenant_id' => $tenant->id,
                'subscription_id' => $nullReceiptSubId ? null : $sub->id,
                'subscription_invoice_id' => $inv->id,
                'amount' => 1000,
                'currency' => 'SAR',
                'gateway' => 'bank_transfer',
                'status' => 'pending',
            ]);
            $receipt = BankTransferReceipt::create([
                'tenant_id' => $tenant->id,
                'subscription_id' => $nullReceiptSubId ? null : $sub->id,
                'subscription_payment_id' => $payment->id,
                'bank_name' => 'Al Rajhi',
                'account_name' => 'Workshop',
                'transfer_reference' => 'REF-LO-'.uniqid(),
                'transfer_amount' => 1000,
                'transfer_date' => now()->toDateString(),
                'receipt_path' => 'receipts/lo.png',
                'status' => BankTransferReceipt::STATUS_PENDING,
            ]);
        }

        return [$tenant, $plan, $admin, $sub, $inv, $receipt];
    }

    public function test_attach_receipt_to_existing_invoice_blocks_cancellation_and_leaves_no_orphans(): void
    {
        [$tenant, , , $sub, $inv] = $this->makeFixture(false);
        $service = app(BankTransferService::class);

        $attached = $service->submitReceipt(
            $tenant,
            [
                'subscription_invoice_id' => $inv->id,
                'transfer_amount' => 1000,
                'transfer_reference' => 'REF-ATT-1',
                'transfer_date' => now()->toDateString(),
                'bank_name' => 'Al Rajhi',
                'account_name' => 'Workshop',
            ],
            UploadedFile::fake()->image('att.png'),
            'idem-att-1'
        );

        $this->assertSame($sub->id, $attached['subscription']->id);
        $this->assertSame($inv->id, $attached['invoice']->id);

        try {
            $service->cancelUnpaidPendingSubscription($sub->fresh(), 'Attempt cancel after attach');
            $this->fail('Expected BankTransferException when cancelling with pending receipt');
        } catch (BankTransferException $e) {
            $this->assertSame(409, $e->statusCode);
        }

        $this->assertSame('pending_payment', $sub->fresh()->status);
        $this->assertSame('pending_payment', $inv->fresh()->status);
        $this->assertSame(1, BankTransferReceipt::withoutGlobalScopes()->where('subscription_id', $sub->id)->count());
    }

    public function test_approve_then_cancel_rejects_cancellation_without_duplicate_payments(): void
    {
        [, , $admin, $sub, $inv, $receipt] = $this->makeFixture(true);
        $service = app(BankTransferService::class);

        $approved = $service->approveReceipt($receipt, $admin, 'Approved');
        $this->assertFalse($approved['already_reviewed']);

        try {
            $service->cancelUnpaidPendingSubscription($sub->fresh(), 'Late cancel');
            $this->fail('Expected BankTransferException when cancelling after approval');
        } catch (BankTransferException $e) {
            $this->assertSame(409, $e->statusCode);
        }

        $this->assertSame('active', $sub->fresh()->status);
        $this->assertSame('paid', $inv->fresh()->status);
        $this->assertSame(1, SubscriptionPayment::withoutGlobalScopes()->where('subscription_invoice_id', $inv->id)->where('status', 'paid')->count());
    }

    public function test_pending_receipt_blocks_cancel_while_rejected_receipt_allows_cancel_preserving_history(): void
    {
        [, , $admin, $sub, $inv, $receipt] = $this->makeFixture(true);
        $service = app(BankTransferService::class);

        // While receipt is still pending, cancel must fail with 409
        try {
            $service->cancelUnpaidPendingSubscription($sub, 'Cancel while pending');
            $this->fail('Expected BankTransferException while receipt is pending');
        } catch (BankTransferException $e) {
            $this->assertSame(409, $e->statusCode);
        }

        // After rejection, cancel succeeds and preserves receipt history
        $service->rejectReceipt($receipt->fresh(), $admin, 'Bad image');
        $service->cancelUnpaidPendingSubscription($sub->fresh(), 'Cancel after rejection');

        $this->assertSame('cancelled', $sub->fresh()->status);
        $this->assertSame('cancelled', $inv->fresh()->status);
        $this->assertSame(BankTransferReceipt::STATUS_REJECTED, $receipt->fresh()->status);
    }

    public function test_approve_receipt_with_null_subscription_id_resolves_subscription_and_is_idempotent(): void
    {
        [, , $admin, $sub, $inv, $receipt] = $this->makeFixture(true, true);
        $this->assertNull($receipt->subscription_id);

        $service = app(BankTransferService::class);

        $first = $service->approveReceipt($receipt, $admin, 'Approve null sub receipt');
        $second = $service->approveReceipt($receipt->fresh(), $admin, 'Duplicate approve');

        $this->assertFalse($first['already_reviewed']);
        $this->assertTrue($second['already_reviewed']);
        $this->assertSame('active', $sub->fresh()->status);
        $this->assertSame('paid', $inv->fresh()->status);
        $this->assertSame(1, SubscriptionPayment::withoutGlobalScopes()->where('subscription_invoice_id', $inv->id)->where('status', 'paid')->count());
    }
}
