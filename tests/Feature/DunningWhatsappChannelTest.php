<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Billing\DunningConfig;
use App\Models\Billing\DunningReminderLog;
use App\Models\Billing\SubscriptionInvoice;
use App\Models\Integration\Integration;
use App\Models\Tenant;
use App\Services\Billing\DunningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Regression test for the dunning WhatsApp channel.
 *
 * THE BUG THIS GUARDS AGAINST
 *
 * DunningService::sendWhatsApp() used to do this:
 *
 *     $sms = app(SmsService::class);
 *     $sms->sendWhatsApp($tenant->phone, $message['body']);
 *
 * SmsService has no sendWhatsApp() method — it only exposes send().
 * The call therefore raised
 *
 *     Error: Call to undefined method App\Services\Messaging\SmsService::sendWhatsApp()
 *
 * WHY THAT WAS SILENT AND STILL BROKE THE FEATURE
 *
 * The dispatcher wraps every channel in `catch (Throwable)`, so the
 * Error was caught, written to dunning_reminder_logs as a FAILED row
 * and scheduled for retry. No crash, no stack trace in the UI — but
 * the tenant never received a single WhatsApp dunning reminder, and
 * the channel kept burning retry attempts forever.
 *
 * A test that only asserts "no exception is thrown" would have PASSED
 * on the broken code, because the dispatcher swallowed the Error. The
 * assertions below therefore check the *audit row status*, which is
 * the only place the difference is observable.
 *
 * The provider HTTP call is faked (Http::fake) — no real external
 * service is contacted, per the project rule against calling real
 * services in tests.
 */
class DunningWhatsappChannelTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Build an overdue invoice plus a tenant that has a phone number
     * and a dunning config whose tier_4 fires the whatsapp channel.
     */
    private function seedOverdueInvoiceWithWhatsappTier(): array
    {
        $tenant = Tenant::factory()->create([
            'phone' => '+966500000001',
            'email' => 'tenant@example.test',
        ]);

        $invoice = SubscriptionInvoice::create([
            'tenant_id' => $tenant->id,
            'invoice_number' => 'SUB-INV-'.uniqid(),
            'subtotal' => 100,
            'vat' => 15,
            'total' => 115,
            'status' => 'pending',
            'issued_at' => now()->subDays(40),
            // 40 days overdue => tier_4 (>= 21 days) is the highest
            // tier whose threshold is met.
            'due_at' => now()->subDays(40),
        ]);

        $config = DunningConfig::create([
            'tenant_id' => $tenant->id,
            'enabled' => true,
            'active_tiers' => 'tier_1,tier_2,tier_3,tier_4',
            'channel_matrix' => [
                'tier_4' => ['whatsapp'],
            ],
            'message_templates' => [],
        ]);

        return [$tenant, $invoice, $config];
    }

    /**
     * An active, default WhatsApp integration so WhatsappService does
     * not throw "لا يوجد مزود WhatsApp مفعّل".
     */
    private function seedActiveWhatsappIntegration(): Integration
    {
        return Integration::create([
            'type' => 'whatsapp',
            'provider' => 'whatsapp_cloud',
            'name' => 'WhatsApp Cloud',
            'name_ar' => 'واتساب سحابي',
            'config' => [
                'access_token' => 'test-token',
                'phone_number_id' => '1234567890',
            ],
            'is_active' => true,
            'is_default' => true,
        ]);
    }

    public function test_whatsapp_channel_records_a_sent_row_when_the_provider_accepts_the_message(): void
    {
        $this->seedActiveWhatsappIntegration();

        // Fake the provider call — no real HTTP request leaves the app.
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'messages' => [['id' => 'wamid.TEST']],
            ], 200),
        ]);

        [$tenant, $invoice, $config] = $this->seedOverdueInvoiceWithWhatsappTier();

        $sent = app(DunningService::class)->sendTier($invoice, 'tier_4', $config);

        $this->assertSame(1, $sent, 'The WhatsApp channel should report one successful send.');

        $log = DunningReminderLog::query()
            ->where('subscription_invoice_id', $invoice->id)
            ->where('tier_key', 'tier_4')
            ->where('channel', 'whatsapp')
            ->first();

        $this->assertNotNull($log, 'A dunning reminder log row should exist for the WhatsApp channel.');
        $this->assertSame(
            DunningReminderLog::STATUS_SENT,
            $log->status,
            'The WhatsApp channel must be logged as SENT. Before the fix this row was '
            .'logged as FAILED because SmsService::sendWhatsApp() did not exist.'
        );
        $this->assertNull($log->error_message, 'A successful send must not carry an error message.');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'graph.facebook.com');
        });
    }

    public function test_whatsapp_channel_does_not_record_an_undefined_method_error(): void
    {
        $this->seedActiveWhatsappIntegration();

        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'messages' => [['id' => 'wamid.TEST']],
            ], 200),
        ]);

        [$tenant, $invoice, $config] = $this->seedOverdueInvoiceWithWhatsappTier();

        app(DunningService::class)->sendTier($invoice, 'tier_4', $config);

        $log = DunningReminderLog::query()
            ->where('subscription_invoice_id', $invoice->id)
            ->where('channel', 'whatsapp')
            ->first();

        $this->assertNotNull($log);
        $this->assertStringNotContainsString(
            'undefined method',
            (string) $log->error_message,
            'The channel must not fail with "Call to undefined method ..." — that was the '
            .'original bug (SmsService has no sendWhatsApp method).'
        );
    }

    public function test_whatsapp_channel_logs_a_failure_when_the_provider_rejects_the_message(): void
    {
        $this->seedActiveWhatsappIntegration();

        Http::fake([
            'graph.facebook.com/*' => Http::response(['error' => ['message' => 'invalid token']], 401),
        ]);

        [$tenant, $invoice, $config] = $this->seedOverdueInvoiceWithWhatsappTier();

        $sent = app(DunningService::class)->sendTier($invoice, 'tier_4', $config);

        $this->assertSame(0, $sent, 'A rejected provider response must not count as a send.');

        $log = DunningReminderLog::query()
            ->where('subscription_invoice_id', $invoice->id)
            ->where('channel', 'whatsapp')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame(
            DunningReminderLog::STATUS_FAILED,
            $log->status,
            'A provider rejection must be recorded as FAILED (not silently swallowed).'
        );
    }
}
