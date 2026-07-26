<?php

declare(strict_types=1);

namespace Tests\Feature\System;

use App\Mail\SubscriptionOverdueMail;
use App\Models\Billing\DunningConfig;
use App\Models\Billing\DunningReminderLog;
use App\Models\Billing\Plan;
use App\Models\Billing\Subscription;
use App\Models\Billing\SubscriptionInvoice;
use App\Models\Tenant;
use App\Services\Billing\DunningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Phase 6 — Dunning automation tests.
 *
 * These tests build a self-contained tenant + invoice + plan setup
 * in memory (using sqlite :memory: via the standard test base) and
 * exercise the DunningService directly. We don't run the cron
 * command itself because that pulls in the queue worker and
 * channels — testing the service in isolation lets us cover the
 * decision logic (which tier fires, when, idempotency, opt-out)
 * without the I/O noise.
 */
class DunningAutomationTest extends TestCase
{
    use RefreshDatabase;

    private function makeTenant(array $overrides = []): Tenant
    {
        return Tenant::create(array_merge([
            'name' => 'Test Tenant',
            'slug' => 'test-tenant',
            'email' => 'test@example.com',
            'phone' => '+966500000000',
            'status' => 'active',
        ], $overrides));
    }

    private function makeInvoice(Tenant $tenant, int $daysOverdue, string $status = 'pending'): SubscriptionInvoice
    {
        $plan = Plan::create([
            'name' => 'Test Plan',
            'name_ar' => 'خطة اختبار',
            'name_en' => 'Test Plan',
            'slug' => 'test-plan-'.uniqid(),
            'price_monthly' => 100,
            'price_yearly' => 1000,
            'trial_days' => 14,
        ]);

        $subscription = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subDays(30),
            'ends_at' => now()->addDays(30),
        ]);

        return SubscriptionInvoice::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'invoice_number' => 'INV-TEST-'.uniqid(),
            'subtotal' => 100,
            'discount' => 0,
            'vat' => 15,
            'total' => 115,
            'status' => $status,
            'issued_at' => now()->subDays(30),
            'due_at' => Carbon::today()->subDays($daysOverdue),
        ]);
    }

    public function test_finds_candidate_at_three_days_overdue_for_tier_one(): void
    {
        Mail::fake();

        $tenant = $this->makeTenant();
        $this->makeInvoice($tenant, 3);

        $service = app(DunningService::class);
        $candidates = $service->findCandidates();

        $this->assertCount(1, $candidates);
        $this->assertSame('tier_1', $candidates[0]['tier_key']);
    }

    public function test_picks_highest_active_tier_based_on_age(): void
    {
        Mail::fake();

        $tenant = $this->makeTenant();
        $this->makeInvoice($tenant, 14);

        $service = app(DunningService::class);
        $candidates = $service->findCandidates();

        $this->assertCount(1, $candidates);
        // 14 days overdue = tier_3 (urgent). tier_1 and tier_2 are
        // also "due" by their day count, but we send the HIGHEST
        // active tier only. The lower tiers are skipped because
        // the dispatcher only fires one tier per run.
        $this->assertSame('tier_3', $candidates[0]['tier_key']);
    }

    public function test_skips_invoices_under_three_days_overdue(): void
    {
        Mail::fake();

        $tenant = $this->makeTenant();
        $this->makeInvoice($tenant, 1);

        $service = app(DunningService::class);
        $candidates = $service->findCandidates();

        $this->assertCount(0, $candidates);
    }

    public function test_skips_paid_invoices(): void
    {
        Mail::fake();

        $tenant = $this->makeTenant();
        $this->makeInvoice($tenant, 5, 'paid');

        $service = app(DunningService::class);
        $candidates = $service->findCandidates();

        $this->assertCount(0, $candidates);
    }

    public function test_respects_tenant_opt_out(): void
    {
        Mail::fake();

        $tenant = $this->makeTenant(['dunning_opted_out' => true]);
        $this->makeInvoice($tenant, 5);

        $service = app(DunningService::class);
        $candidates = $service->findCandidates();

        $this->assertCount(0, $candidates, 'Opted-out tenants must be skipped entirely');
    }

    public function test_skips_when_dunning_disabled_in_config(): void
    {
        Mail::fake();

        $tenant = $this->makeTenant();
        DunningConfig::create([
            'tenant_id' => null,
            'enabled' => false,
            'active_tiers' => 'tier_1',
            'channel_matrix' => DunningConfig::defaultChannelMatrix(),
        ]);
        $this->makeInvoice($tenant, 5);

        $service = app(DunningService::class);
        $candidates = $service->findCandidates();

        $this->assertCount(0, $candidates);
    }

    public function test_sends_email_through_default_channel_matrix(): void
    {
        Mail::fake();

        $tenant = $this->makeTenant();
        $invoice = $this->makeInvoice($tenant, 5);

        $service = app(DunningService::class);
        $service->ensureGlobalDefault();
        $candidates = $service->findCandidates();
        $this->assertCount(1, $candidates);

        $sent = $service->sendTier($invoice, 'tier_1', $candidates[0]['config']);
        $this->assertSame(1, $sent, 'Tier 1 default channel matrix is [email], so 1 channel should send');

        // The mailable is ShouldQueue, so with QUEUE_CONNECTION=sync
        // the queue worker executes it inline and consumes it from
        // the mail fake's queue. assertQueued is the correct
        // assertion for queued mailables.
        Mail::assertQueued(SubscriptionOverdueMail::class, 1);

        // Verify the log row was created
        $this->assertDatabaseHas('dunning_reminder_logs', [
            'tenant_id' => $tenant->id,
            'subscription_invoice_id' => $invoice->id,
            'tier_key' => 'tier_1',
            'channel' => 'email',
            'status' => 'sent',
        ]);
    }

    public function test_idempotency_same_tier_cannot_be_sent_twice(): void
    {
        Mail::fake();

        $tenant = $this->makeTenant();
        $invoice = $this->makeInvoice($tenant, 5);
        $service = app(DunningService::class);
        $global = $service->ensureGlobalDefault();

        // First send — should succeed
        $firstCount = $service->sendTier($invoice, 'tier_1', $global);
        $this->assertSame(1, $firstCount);

        // Second send of the same tier for the same invoice — should
        // be a no-op (the unique constraint blocks the insert and
        // the service catches it as a SKIPPED log row, not a throw).
        $secondCount = $service->sendTier($invoice, 'tier_1', $global);
        $this->assertSame(0, $secondCount, 'Re-sending the same tier should not send another email');

        Mail::assertQueued(SubscriptionOverdueMail::class, 1);

        // Verify only ONE 'sent' row exists. The second attempt
        // does NOT log a separate SKIPPED row because the unique
        // constraint on (invoice, tier, channel) only allows one
        // row per tier per invoice — the SENT row from the first
        // attempt IS the audit trail.
        $sentCount = DunningReminderLog::where('status', 'sent')->count();
        $skippedCount = DunningReminderLog::where('status', 'skipped')->count();
        $this->assertSame(1, $sentCount);
        $this->assertSame(0, $skippedCount);
    }

    public function test_message_body_is_in_arabic_when_language_is_arabic(): void
    {
        $tenant = $this->makeTenant(['dunning_language' => 'ar']);
        $invoice = $this->makeInvoice($tenant, 5);

        $service = app(DunningService::class);
        $message = $service->buildMessageBody($invoice, 'tier_1', 'ar', 5);

        // The Arabic template uses "تذكير" in the subject and body.
        $this->assertStringContainsString('تذكير', $message['subject']);
        $this->assertStringContainsString('تذكير', $message['body']);
        $this->assertStringContainsString($invoice->invoice_number, $message['body']);
    }

    public function test_message_body_is_in_english_when_language_is_english(): void
    {
        $tenant = $this->makeTenant(['dunning_language' => 'en']);
        $invoice = $this->makeInvoice($tenant, 5);

        $service = app(DunningService::class);
        $message = $service->buildMessageBody($invoice, 'tier_1', 'en', 5);

        $this->assertStringContainsString('Friendly', $message['subject']);
        $this->assertStringContainsString('reminder', $message['body']);
    }

    public function test_per_tenant_config_overrides_global(): void
    {
        Mail::fake();

        $tenant = $this->makeTenant();

        // Per-tenant override: disable all tiers for this tenant
        DunningConfig::create([
            'tenant_id' => $tenant->id,
            'enabled' => true,
            'active_tiers' => '',
            'channel_matrix' => DunningConfig::defaultChannelMatrix(),
        ]);

        $this->makeInvoice($tenant, 5);

        $service = app(DunningService::class);
        $candidates = $service->findCandidates();
        $this->assertCount(0, $candidates, 'Per-tenant override with empty active_tiers should skip the tenant');
    }

    public function test_message_body_includes_invoice_number_and_total(): void
    {
        $tenant = $this->makeTenant();
        $invoice = $this->makeInvoice($tenant, 5);

        $service = app(DunningService::class);
        $message = $service->buildMessageBody($invoice, 'tier_1', 'en', 5);

        $this->assertStringContainsString($invoice->invoice_number, $message['body']);
        // 115.00 — formatted with 2 decimals
        $this->assertStringContainsString('115.00', $message['body']);
    }
}
