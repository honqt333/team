<?php

declare(strict_types=1);

namespace Tests\Feature\System;

use App\Models\BankTransferReceipt;
use App\Models\Billing\Plan;
use App\Models\Billing\Subscription;
use App\Models\Billing\SubscriptionInvoice;
use App\Models\Billing\SubscriptionPayment;
use App\Models\PaymentSettings;
use App\Models\Tenant;
use App\Services\Billing\BankTransferService;
use App\Support\Permissions;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class BankTransferServiceFoundationTest extends TestCase
{
    use RefreshDatabase;

    private BankTransferService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(BankTransferService::class);
    }

    public function test_tracking_columns_and_override_permission_exist(): void
    {
        $this->assertTrue(Schema::hasColumn('bank_transfer_receipts', 'approved_amount'));
        $this->assertTrue(Schema::hasColumn('bank_transfer_receipts', 'idempotency_key'));
        $this->assertTrue(Schema::hasColumn('subscription_invoices', 'operation_type'));
        $this->assertSame('system.receipts.override', Permissions::SYSTEM_RECEIPTS_OVERRIDE);
    }

    public function test_exact_halala_arithmetic_avoids_float_drift(): void
    {
        $this->assertSame(1999, $this->service->toHalalas('19.99'));
        $this->assertSame(114999, $this->service->toHalalas(1149.99));
        $this->assertSame('1149.99', $this->service->fromHalalas(114999));
        $this->assertSame(100, $this->service->toHalalas('0.995'));
    }

    public function test_paid_and_remaining_halalas_exclude_non_paid_and_refunded_payments(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::create([
            'name' => 'Basic Plan',
            'name_ar' => 'الباقة الأساسية',
            'name_en' => 'Basic Plan',
            'slug' => 'basic-plan-'.uniqid(),
            'price_monthly' => 100,
            'price_yearly' => 1000,
            'is_active' => true,
        ]);
        $subscription = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'pending_payment',
            'billing_cycle' => 'yearly',
            'price' => 1000,
        ]);
        $invoice = SubscriptionInvoice::create([
            'subscription_id' => $subscription->id,
            'tenant_id' => $tenant->id,
            'invoice_number' => 'INV-TEST-0001',
            'operation_type' => SubscriptionInvoice::OPERATION_NEW,
            'subtotal' => 1000,
            'vat' => 150,
            'discount' => 0,
            'total' => 1150.00,
            'status' => 'pending',
            'issued_at' => now(),
            'due_at' => now()->addDays(7),
        ]);

        SubscriptionPayment::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'subscription_invoice_id' => $invoice->id,
            'amount' => 400.00,
            'currency' => 'SAR',
            'gateway' => 'bank_transfer',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        // Must be excluded: refunded_at set
        SubscriptionPayment::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'subscription_invoice_id' => $invoice->id,
            'amount' => 200.00,
            'currency' => 'SAR',
            'gateway' => 'bank_transfer',
            'status' => 'paid',
            'paid_at' => now(),
            'refunded_at' => now(),
        ]);

        // Must be excluded: failed / pending / refunded status
        foreach (['pending', 'failed', 'refunded'] as $status) {
            SubscriptionPayment::create([
                'tenant_id' => $tenant->id,
                'subscription_id' => $subscription->id,
                'subscription_invoice_id' => $invoice->id,
                'amount' => 300.00,
                'currency' => 'SAR',
                'gateway' => 'bank_transfer',
                'status' => $status,
            ]);
        }

        $this->assertSame(40000, $this->service->getPaidHalalas($invoice));
        $this->assertSame(75000, $this->service->getRemainingHalalas($invoice));
    }

    public function test_idempotency_key_is_unique_per_tenant_on_receipts(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        BankTransferReceipt::create([
            'tenant_id' => $tenantA->id,
            'transfer_amount' => 500,
            'transfer_reference' => 'REF-1',
            'idempotency_key' => 'idem-key-123',
            'transfer_date' => now()->toDateString(),
            'bank_name' => 'Al Rajhi',
            'account_name' => 'Test',
            'status' => 'pending',
        ]);

        // Same key on another tenant is allowed
        $other = BankTransferReceipt::create([
            'tenant_id' => $tenantB->id,
            'transfer_amount' => 500,
            'transfer_reference' => 'REF-2',
            'idempotency_key' => 'idem-key-123',
            'transfer_date' => now()->toDateString(),
            'bank_name' => 'Al Rajhi',
            'account_name' => 'Test',
            'status' => 'pending',
        ]);
        $this->assertNotNull($other->id);

        // Same key on the same tenant violates unique constraint
        $this->expectException(QueryException::class);
        BankTransferReceipt::create([
            'tenant_id' => $tenantA->id,
            'transfer_amount' => 500,
            'transfer_reference' => 'REF-3',
            'idempotency_key' => 'idem-key-123',
            'transfer_date' => now()->toDateString(),
            'bank_name' => 'Al Rajhi',
            'account_name' => 'Test',
            'status' => 'pending',
        ]);
    }

    public function test_resolve_operation_type_uses_explicit_value_and_halts_when_ambiguous(): void
    {
        $tenant = Tenant::factory()->create();
        $plan = Plan::create([
            'name' => 'Pro Plan',
            'name_ar' => 'الباقة الاحترافية',
            'name_en' => 'Pro Plan',
            'slug' => 'pro-plan-'.uniqid(),
            'price_monthly' => 100,
            'price_yearly' => 1000,
            'is_active' => true,
        ]);
        $activeSub = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => 'yearly',
            'price' => 1000,
        ]);

        $explicitRenewal = SubscriptionInvoice::create([
            'subscription_id' => $activeSub->id,
            'tenant_id' => $tenant->id,
            'invoice_number' => 'INV-EXP-1',
            'operation_type' => SubscriptionInvoice::OPERATION_RENEWAL,
            'subtotal' => 1000,
            'vat' => 0,
            'discount' => 0,
            'total' => 1000,
            'status' => 'pending',
            'issued_at' => now(),
            'due_at' => now()->addDays(7),
        ]);

        $this->assertSame(
            SubscriptionInvoice::OPERATION_RENEWAL,
            $this->service->resolveOperationType($explicitRenewal)
        );

        // Legacy active subscription invoice without prior paid invoice and without operation_type is ambiguous -> halt safely
        $ambiguousInvoice = SubscriptionInvoice::create([
            'subscription_id' => $activeSub->id,
            'tenant_id' => $tenant->id,
            'invoice_number' => 'INV-AMB-1',
            'operation_type' => null,
            'subtotal' => 1000,
            'vat' => 0,
            'discount' => 0,
            'total' => 1000,
            'status' => 'pending',
            'issued_at' => now(),
            'due_at' => now()->addDays(7),
        ]);

        $this->expectException(RuntimeException::class);
        $this->service->resolveOperationType($ambiguousInvoice);
    }

    public function test_policy_config_reads_payment_settings_accurately(): void
    {
        PaymentSettings::query()->delete();
        PaymentSettings::create([
            'bank_transfer_enabled' => true,
            'bank_transfer_min_amount' => 150.50,
            'bank_transfer_max_days' => 14,
            'bank_transfer_require_receipt' => false,
        ]);

        $policy = $this->service->getPolicyConfig();

        $this->assertSame(15050, $policy['min_amount_halalas']);
        $this->assertSame(14, $policy['max_days']);
        $this->assertFalse($policy['require_receipt']);
    }
}
