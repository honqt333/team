<?php

declare(strict_types=1);

namespace Tests\Feature\App;

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
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CompanySubscriptionBankTransferTest extends TestCase
{
    use RefreshDatabase;

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
    }

    /**
     * @return array{0: Tenant, 1: Center, 2: User}
     */
    protected function createAuthenticatedTenantUser(): array
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

        return [$tenant, $center, $user];
    }

    protected function createPlan(float $priceYearly = 1000.00): Plan
    {
        return Plan::create([
            'name' => 'Pro Plan',
            'name_ar' => 'باقة متقدمة',
            'name_en' => 'Pro Plan',
            'slug' => 'pro-'.uniqid(),
            'price_monthly' => $priceYearly / 10,
            'price_yearly' => $priceYearly,
            'is_active' => true,
        ]);
    }

    public function test_subscribe_bank_transfer_returns_setup_and_creates_no_orphan_records(): void
    {
        [$tenant, , $user] = $this->createAuthenticatedTenantUser();
        $plan = $this->createPlan(1200.00);

        PaymentSettings::query()->first()->update([
            'bank_transfer_min_amount' => 500,
            'bank_transfer_max_days' => 10,
            'bank_transfer_require_receipt' => false,
        ]);

        $response = $this->actingAs($user)
            ->postJson('/app/settings/billing/subscribe', [
                'plan_id' => $plan->id,
                'billing_cycle' => 'yearly',
                'gateway' => 'bank_transfer',
            ]);

        $response->assertOk()
            ->assertJson([
                'bank_transfer_setup' => true,
                'bank_transfer_min_amount' => 500.0,
                'bank_transfer_max_days' => 10,
                'bank_transfer_require_receipt' => false,
            ]);

        $this->assertSame(0, Subscription::where('tenant_id', $tenant->id)->count());
        $this->assertSame(0, SubscriptionInvoice::where('tenant_id', $tenant->id)->count());
    }

    public function test_min_amount_max_days_and_require_receipt_settings_are_enforced(): void
    {
        [$tenant, , $user] = $this->createAuthenticatedTenantUser();
        $plan = $this->createPlan(400.00);

        PaymentSettings::query()->first()->update([
            'bank_transfer_min_amount' => 500.00,
            'bank_transfer_max_days' => 14,
            'bank_transfer_require_receipt' => false,
        ]);

        // Plan total (400) < min_amount (500) -> subscribe rejects
        $this->actingAs($user)
            ->postJson('/app/settings/billing/subscribe', [
                'plan_id' => $plan->id,
                'gateway' => 'bank_transfer',
            ])
            ->assertStatus(422);

        // Transfer amount below min_amount -> upload rejects
        $this->actingAs($user)
            ->postJson('/app/settings/billing/bank-transfer-receipt', [
                'plan_id' => $plan->id,
                'transfer_amount' => 300,
                'transfer_reference' => 'REF-LOW-1',
                'transfer_date' => now()->toDateString(),
                'bank_name' => 'Al Rajhi',
                'account_name' => 'Workshop',
            ])
            ->assertStatus(422);

        // Now use a plan >= min_amount (600) and upload WITHOUT receipt file (require_receipt = false)
        $bigPlan = $this->createPlan(600.00);
        $okResponse = $this->actingAs($user)
            ->postJson('/app/settings/billing/bank-transfer-receipt', [
                'plan_id' => $bigPlan->id,
                'transfer_amount' => 600,
                'transfer_reference' => 'REF-NO-FILE-1',
                'transfer_date' => now()->toDateString(),
                'bank_name' => 'Al Rajhi',
                'account_name' => 'Workshop',
            ]);

        $okResponse->assertOk()->assertJson(['success' => true]);

        $invoice = SubscriptionInvoice::where('tenant_id', $tenant->id)->firstOrFail();
        $this->assertSame(SubscriptionInvoice::OPERATION_NEW, $invoice->operation_type);
        $this->assertSame(now()->addDays(14)->toDateString(), $invoice->due_at->toDateString());
    }

    public function test_invalid_monetary_and_date_inputs_are_rejected(): void
    {
        [, , $user] = $this->createAuthenticatedTenantUser();
        $plan = $this->createPlan(1000.00);

        $this->actingAs($user)
            ->postJson('/app/settings/billing/bank-transfer-receipt', [
                'plan_id' => $plan->id,
                'transfer_amount' => -50,
                'transfer_reference' => 'REF-NEG',
                'transfer_date' => now()->addDay()->toDateString(),
                'bank_name' => 'Al Rajhi',
                'account_name' => 'Workshop',
                'receipt' => UploadedFile::fake()->image('receipt.png'),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['transfer_amount', 'transfer_date']);
    }

    public function test_existing_invoice_flow_attaches_payment_without_duplicating_subscription_or_invoice(): void
    {
        [$tenant, , $user] = $this->createAuthenticatedTenantUser();
        $plan = $this->createPlan(1000.00);

        $subscription = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => 'yearly',
            'price' => 1000,
        ]);

        $invoice = SubscriptionInvoice::create([
            'subscription_id' => $subscription->id,
            'tenant_id' => $tenant->id,
            'invoice_number' => 'INV-REN-100',
            'operation_type' => SubscriptionInvoice::OPERATION_RENEWAL,
            'subtotal' => 1000,
            'vat' => 0,
            'discount' => 0,
            'total' => 1000,
            'status' => 'pending',
            'issued_at' => now(),
            'due_at' => now()->addDays(7),
        ]);

        $response = $this->actingAs($user)
            ->postJson('/app/settings/billing/bank-transfer-receipt', [
                'subscription_invoice_id' => $invoice->id,
                'transfer_amount' => 1000,
                'transfer_reference' => 'REF-INV-100',
                'transfer_date' => now()->toDateString(),
                'bank_name' => 'SNB',
                'account_name' => 'Workshop',
                'receipt' => UploadedFile::fake()->image('receipt.jpg'),
            ]);

        $response->assertOk()->assertJson(['success' => true]);

        // No duplicate subscription or invoice created
        $this->assertSame(1, Subscription::where('tenant_id', $tenant->id)->count());
        $this->assertSame(1, SubscriptionInvoice::where('tenant_id', $tenant->id)->count());
        $this->assertSame(1, SubscriptionPayment::where('subscription_invoice_id', $invoice->id)->count());

        // Second pending receipt on the same invoice is rejected with 409
        $this->actingAs($user)
            ->postJson('/app/settings/billing/bank-transfer-receipt', [
                'subscription_invoice_id' => $invoice->id,
                'transfer_amount' => 1000,
                'transfer_reference' => 'REF-INV-101',
                'transfer_date' => now()->toDateString(),
                'bank_name' => 'SNB',
                'account_name' => 'Workshop',
                'receipt' => UploadedFile::fake()->image('receipt2.jpg'),
            ])
            ->assertStatus(409);
    }

    public function test_other_tenant_invoice_and_paid_invoice_are_rejected(): void
    {
        [$tenant, , $user] = $this->createAuthenticatedTenantUser();
        $otherTenant = Tenant::factory()->create();
        $plan = $this->createPlan(1000.00);

        $otherSub = Subscription::create([
            'tenant_id' => $otherTenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => 'yearly',
            'price' => 1000,
        ]);
        $otherInvoice = SubscriptionInvoice::create([
            'subscription_id' => $otherSub->id,
            'tenant_id' => $otherTenant->id,
            'invoice_number' => 'INV-OTHER-1',
            'operation_type' => SubscriptionInvoice::OPERATION_RENEWAL,
            'subtotal' => 1000,
            'vat' => 0,
            'discount' => 0,
            'total' => 1000,
            'status' => 'pending',
            'issued_at' => now(),
            'due_at' => now()->addDays(7),
        ]);

        // Cross-tenant invoice rejected
        $this->actingAs($user)
            ->postJson('/app/settings/billing/bank-transfer-receipt', [
                'subscription_invoice_id' => $otherInvoice->id,
                'transfer_amount' => 1000,
                'transfer_reference' => 'REF-X-1',
                'transfer_date' => now()->toDateString(),
                'bank_name' => 'SNB',
                'account_name' => 'Workshop',
                'receipt' => UploadedFile::fake()->image('receipt.jpg'),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['subscription_invoice_id']);

        // Paid own invoice rejected
        $ownSub = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => 'yearly',
            'price' => 1000,
        ]);
        $paidInvoice = SubscriptionInvoice::create([
            'subscription_id' => $ownSub->id,
            'tenant_id' => $tenant->id,
            'invoice_number' => 'INV-PAID-1',
            'operation_type' => SubscriptionInvoice::OPERATION_NEW,
            'subtotal' => 1000,
            'vat' => 0,
            'discount' => 0,
            'total' => 1000,
            'status' => 'paid',
            'issued_at' => now(),
            'due_at' => now()->addDays(7),
            'paid_at' => now(),
        ]);

        $this->actingAs($user)
            ->postJson('/app/settings/billing/bank-transfer-receipt', [
                'subscription_invoice_id' => $paidInvoice->id,
                'transfer_amount' => 1000,
                'transfer_reference' => 'REF-PAID-1',
                'transfer_date' => now()->toDateString(),
                'bank_name' => 'SNB',
                'account_name' => 'Workshop',
                'receipt' => UploadedFile::fake()->image('receipt.jpg'),
            ])
            ->assertStatus(422);
    }

    public function test_rejected_receipt_allows_resubmission_on_same_subscription_and_invoice(): void
    {
        [$tenant, , $user] = $this->createAuthenticatedTenantUser();
        $plan = $this->createPlan(1000.00);

        $sub = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'pending_payment',
            'billing_cycle' => 'yearly',
            'price' => 1000,
        ]);
        $invoice = SubscriptionInvoice::create([
            'subscription_id' => $sub->id,
            'tenant_id' => $tenant->id,
            'invoice_number' => 'INV-REJ-1',
            'operation_type' => SubscriptionInvoice::OPERATION_NEW,
            'subtotal' => 1000,
            'vat' => 0,
            'discount' => 0,
            'total' => 1000,
            'status' => 'pending',
            'issued_at' => now(),
            'due_at' => now()->addDays(7),
        ]);
        $oldPayment = SubscriptionPayment::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $sub->id,
            'subscription_invoice_id' => $invoice->id,
            'amount' => 1000,
            'currency' => 'SAR',
            'gateway' => 'bank_transfer',
            'status' => 'failed',
        ]);
        BankTransferReceipt::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $sub->id,
            'subscription_payment_id' => $oldPayment->id,
            'transfer_amount' => 1000,
            'transfer_reference' => 'REF-OLD-1',
            'transfer_date' => now()->toDateString(),
            'bank_name' => 'Al Rajhi',
            'account_name' => 'Workshop',
            'status' => BankTransferReceipt::STATUS_REJECTED,
        ]);

        $response = $this->actingAs($user)
            ->postJson('/app/settings/billing/bank-transfer-receipt', [
                'plan_id' => $plan->id,
                'transfer_amount' => 1000,
                'transfer_reference' => 'REF-NEW-2',
                'transfer_date' => now()->toDateString(),
                'bank_name' => 'Al Rajhi',
                'account_name' => 'Workshop',
                'receipt' => UploadedFile::fake()->image('receipt-fixed.png'),
            ]);

        $response->assertOk()->assertJson(['success' => true]);

        // Reused same subscription & invoice without duplicating core records
        $this->assertSame(1, Subscription::where('tenant_id', $tenant->id)->count());
        $this->assertSame(1, SubscriptionInvoice::where('tenant_id', $tenant->id)->count());
        $this->assertSame(2, BankTransferReceipt::where('subscription_id', $sub->id)->count());
    }

    public function test_idempotency_key_replays_identical_request_and_rejects_mismatched_payload(): void
    {
        [$tenant, , $user] = $this->createAuthenticatedTenantUser();
        $plan = $this->createPlan(1000.00);
        $key = 'idem-submit-001';

        $payload = [
            'plan_id' => $plan->id,
            'transfer_amount' => 1000,
            'transfer_reference' => 'REF-IDEM-1',
            'transfer_date' => now()->toDateString(),
            'bank_name' => 'Al Rajhi',
            'account_name' => 'Workshop',
            'receipt' => UploadedFile::fake()->image('receipt.png'),
        ];

        $first = $this->actingAs($user)
            ->withHeaders(['Idempotency-Key' => $key])
            ->postJson('/app/settings/billing/bank-transfer-receipt', $payload);

        $first->assertOk()->assertJson([
            'success' => true,
            'idempotent_replay' => false,
        ]);

        // Identical retry with same Idempotency-Key returns replay without creating duplicates
        $retryPayload = $payload;
        $retryPayload['receipt'] = UploadedFile::fake()->image('receipt.png');

        $second = $this->actingAs($user)
            ->withHeaders(['Idempotency-Key' => $key])
            ->postJson('/app/settings/billing/bank-transfer-receipt', $retryPayload);

        $second->assertOk()->assertJson([
            'success' => true,
            'idempotent_replay' => true,
        ]);
        $this->assertSame($first->json('receipt.id'), $second->json('receipt.id'));
        $this->assertSame(1, BankTransferReceipt::where('tenant_id', $tenant->id)->count());

        // Different payload with same Idempotency-Key is rejected with 409
        $mismatchPayload = $payload;
        $mismatchPayload['transfer_amount'] = 900;
        $mismatchPayload['transfer_reference'] = 'REF-IDEM-DIFF';
        $mismatchPayload['receipt'] = UploadedFile::fake()->image('receipt.png');

        $this->actingAs($user)
            ->withHeaders(['Idempotency-Key' => $key])
            ->postJson('/app/settings/billing/bank-transfer-receipt', $mismatchPayload)
            ->assertStatus(409);
    }

    public function test_receipt_upload_persists_file_tracking_columns_and_rejects_invalid_files_cleanly(): void
    {
        [$tenant, , $user] = $this->createAuthenticatedTenantUser();
        $plan = $this->createPlan(1000.00);

        // Invalid extension (.txt) returns 422 without leaving files or financial records
        $invalidResponse = $this->actingAs($user)
            ->withHeaders(['Idempotency-Key' => 'invalid-file-key'])
            ->postJson('/app/settings/billing/bank-transfer-receipt', [
                'plan_id' => $plan->id,
                'transfer_amount' => 1000,
                'transfer_reference' => 'REF-INVALID-TXT',
                'transfer_date' => now()->toDateString(),
                'bank_name' => 'Al Rajhi',
                'account_name' => 'Workshop',
                'receipt' => UploadedFile::fake()->create('receipt.txt', 100, 'text/plain'),
            ]);

        $invalidResponse->assertStatus(422)->assertJsonValidationErrors(['receipt']);
        $this->assertSame([], Storage::disk('local')->allFiles("receipts/{$tenant->id}"));
        $this->assertSame(0, Subscription::where('tenant_id', $tenant->id)->count());
        $this->assertSame(0, SubscriptionInvoice::where('tenant_id', $tenant->id)->count());
        $this->assertSame(0, SubscriptionPayment::where('tenant_id', $tenant->id)->count());
        $this->assertSame(0, BankTransferReceipt::where('tenant_id', $tenant->id)->count());

        // Valid webp receipt matching frontend acceptedTypes succeeds and stores file + tracking columns
        $validKey = 'valid-webp-upload-001';
        $validResponse = $this->actingAs($user)
            ->withHeaders(['Idempotency-Key' => $validKey])
            ->postJson('/app/settings/billing/bank-transfer-receipt', [
                'plan_id' => $plan->id,
                'billing_cycle' => 'yearly',
                'transfer_amount' => 1000,
                'transfer_reference' => 'REF-WEBP-001',
                'transfer_date' => now()->toDateString(),
                'bank_name' => 'Al Rajhi',
                'account_name' => 'Workshop',
                'receipt' => UploadedFile::fake()->create('receipt.webp', 200, 'image/webp'),
            ]);

        $validResponse->assertOk()->assertJson([
            'success' => true,
            'idempotent_replay' => false,
        ]);

        $receipt = BankTransferReceipt::findOrFail($validResponse->json('receipt.id'));
        $payment = SubscriptionPayment::findOrFail($validResponse->json('payment.id'));
        $invoice = SubscriptionInvoice::findOrFail($validResponse->json('invoice.id'));
        $subscription = Subscription::findOrFail($validResponse->json('subscription.id'));

        $this->assertSame($tenant->id, (int) $receipt->tenant_id);
        $this->assertSame($subscription->id, (int) $receipt->subscription_id);
        $this->assertSame($payment->id, (int) $receipt->subscription_payment_id);
        $this->assertSame($invoice->id, (int) $payment->subscription_invoice_id);
        $this->assertSame($validKey, $receipt->idempotency_key);
        $this->assertNotEmpty($receipt->request_hash);
        $this->assertSame(SubscriptionInvoice::OPERATION_NEW, $invoice->operation_type);
        $this->assertNotEmpty($receipt->receipt_path);
        Storage::disk('local')->assertExists($receipt->receipt_path);
    }

    public function test_receipt_upload_supports_jpg_pdf_and_rejects_oversized_files(): void
    {
        [$tenant1, , $user1] = $this->createAuthenticatedTenantUser();
        [$tenant2, , $user2] = $this->createAuthenticatedTenantUser();
        $plan = $this->createPlan(1000.00);

        // Oversized (> 10240 KB) rejected with 422 and zero orphan files/records
        $this->actingAs($user1)
            ->postJson('/app/settings/billing/bank-transfer-receipt', [
                'plan_id' => $plan->id,
                'transfer_amount' => 1000,
                'transfer_reference' => 'REF-OVERSIZE-1',
                'transfer_date' => now()->toDateString(),
                'bank_name' => 'Al Rajhi',
                'account_name' => 'Workshop',
                'receipt' => UploadedFile::fake()->create('huge.pdf', 11000, 'application/pdf'),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['receipt']);

        $this->assertSame([], Storage::disk('local')->allFiles("receipts/{$tenant1->id}"));
        $this->assertSame(0, BankTransferReceipt::where('tenant_id', $tenant1->id)->count());

        // Valid JPG
        $jpgRes = $this->actingAs($user1)
            ->postJson('/app/settings/billing/bank-transfer-receipt', [
                'plan_id' => $plan->id,
                'transfer_amount' => 1000,
                'transfer_reference' => 'REF-JPG-001',
                'transfer_date' => now()->toDateString(),
                'bank_name' => 'Al Rajhi',
                'account_name' => 'Workshop',
                'receipt' => UploadedFile::fake()->image('receipt.jpg'),
            ]);
        $jpgRes->assertOk()->assertJson(['success' => true]);
        Storage::disk('local')->assertExists(BankTransferReceipt::findOrFail($jpgRes->json('receipt.id'))->receipt_path);

        // Valid PDF
        $pdfRes = $this->actingAs($user2)
            ->postJson('/app/settings/billing/bank-transfer-receipt', [
                'plan_id' => $plan->id,
                'transfer_amount' => 1000,
                'transfer_reference' => 'REF-PDF-001',
                'transfer_date' => now()->toDateString(),
                'bank_name' => 'Al Rajhi',
                'account_name' => 'Workshop 2',
                'receipt' => UploadedFile::fake()->create('receipt.pdf', 250, 'application/pdf'),
            ]);
        $pdfRes->assertOk()->assertJson(['success' => true]);
        Storage::disk('local')->assertExists(BankTransferReceipt::findOrFail($pdfRes->json('receipt.id'))->receipt_path);
    }
}
