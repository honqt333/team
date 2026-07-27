<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Mail\SubscriptionOverdueMail;
use App\Models\Billing\DunningConfig;
use App\Models\Billing\DunningReminderLog;
use App\Models\Billing\SubscriptionInvoice;
use App\Models\Tenant;
use App\Services\Invoice\SubscriptionInvoiceService;
use App\Services\Messaging\SmsService;
use App\Services\NotificationService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Dunning (collection automation) — Phase 6.
 *
 * Core service that:
 *  1. Finds overdue invoices that are due for a reminder
 *  2. Resolves the tier (3d/7d/14d/30d) based on the days overdue
 *  3. Resolves the channel matrix from the per-tenant config
 *     (falling back to the global default)
 *  4. Sends the reminder through each active channel
 *  5. Logs the attempt (success / failure / skip) for audit
 *
 * Tier resolution rule: an invoice at 8 days overdue qualifies for
 * tier_2 (7d) but NOT yet tier_3 (14d). We pick the highest
 * active tier whose day threshold is <= the invoice's age. The
 * idempotency guard on dunning_reminder_logs (unique per
 * invoice+tier+channel) ensures we never re-send the same tier
 * even if the cron fires more than once per day.
 *
 * Why a service, not a job? The cron command does the "find
 * candidates" pass and dispatches one SendDunningReminderJob per
 * (invoice, tier, channel) combination. Splitting the dispatch
 * into a job per send means a slow SMS provider on tier_3 of
 * invoice #42 doesn't block tier_1 of invoice #99.
 */
class DunningService
{
    /**
     * Find all invoices that are currently overdue AND that have
     * an un-sent (or un-attempted) active tier ready to fire.
     *
     * Returned as a Collection of:
     *   ['invoice' => SubscriptionInvoice, 'tier_key' => 'tier_1']
     *
     * This is the candidate list the cron iterates over. The
     * dispatcher below turns each entry into one or more send
     * jobs (one per channel in the tier's channel matrix).
     *
     * @return array<int, array{invoice: SubscriptionInvoice, tier_key: string}>
     */
    public function findCandidates(): array
    {
        // We compare against `now()` rather than `Carbon::today()` so
        // the "days overdue" count reflects the exact elapsed time
        // since the due date. Using today's midnight would under-
        // count invoices whose due_at has a non-midnight time
        // component (e.g. due_at = "yesterday 18:55" would read
        // as 1 day old at "today 00:00", even though it's clearly
        // past the customer-facing 1-day boundary).
        $now = Carbon::now();
        $candidates = [];

        // Make sure the global default config row exists BEFORE
        // we look up any tenant config — without this, a fresh
        // install has zero dunning_configs rows and every tenant
        // gets skipped because configFor() returns null.
        $this->ensureGlobalDefault();

        // Pull every overdue invoice once, with the tenant joined.
        // We can't rely on a single Eloquent query per tier because
        // SQLite/MySQL handle BETWEEN + computed dates differently
        // and we want the test to be portable.
        $overdueInvoices = SubscriptionInvoice::query()
            ->with('tenant')
            ->where('status', 'pending')
            ->whereNotNull('due_at')
            ->where('due_at', '<', $now)
            ->get();

        foreach ($overdueInvoices as $invoice) {
            $tenant = $invoice->tenant;

            if (! $tenant) {
                continue;
            }

            // Hard skip: tenant opted out of dunning entirely.
            if ($tenant->dunning_opted_out) {
                continue;
            }

            $config = $this->configFor($tenant);

            if (! $config || ! $config->enabled) {
                continue;
            }

            $daysOverdue = (int) abs($now->diffInDays(Carbon::parse($invoice->due_at)));

            $activeTiers = array_filter(
                explode(',', $config->active_tiers),
                fn ($t) => in_array(trim($t), array_keys(DunningConfig::tierDefinitions()), true),
            );

            // Pick the highest active tier whose day threshold is met.
            // Sort tier keys by their day count DESC so we land on
            // the most-urgent tier the invoice currently qualifies
            // for, and skip lower tiers — they're already in the log.
            $tierDays = [];

            foreach ($activeTiers as $tierKey) {
                $tierDays[$tierKey] = DunningConfig::tierDefinitions()[$tierKey]['days'];
            }
            arsort($tierDays); // tier_4, tier_3, tier_2, tier_1

            $selectedTier = null;

            foreach ($tierDays as $tierKey => $threshold) {
                if ($daysOverdue >= $threshold) {
                    $selectedTier = $tierKey;
                    break;
                }
            }

            if ($selectedTier === null) {
                continue; // not yet at tier 1 (3 days)
            }

            $candidates[] = [
                'invoice' => $invoice,
                'tier_key' => $selectedTier,
                'config' => $config,
                'days_overdue' => $daysOverdue,
            ];
        }

        return $candidates;
    }

    /**
     * Send a single tier for a single invoice through every active
     * channel in the channel matrix. Returns the number of channels
     * that actually sent (success path).
     */
    public function sendTier(SubscriptionInvoice $invoice, string $tierKey, DunningConfig $config): int
    {
        $tenant = $invoice->tenant;

        if (! $tenant) {
            return 0;
        }

        $matrix = $config->channel_matrix ?: DunningConfig::defaultChannelMatrix();
        $channels = $matrix[$tierKey] ?? [];

        // Phase 7.3 — quiet hours check. If the current time
        // falls inside the configured quiet window, skip the
        // entire send for this invoice. The cron will pick it
        // up again tomorrow morning. We don't log anything for
        // skipped-by-quiet-hours because the next cron run will
        // succeed and that's what the audit log will show.
        if ($this->isInQuietHours($config)) {
            return 0;
        }

        // Phase 7.2 — language fallback chain. The tenant's
        // explicit dunning_language always wins. If that's
        // null, fall through to the app's current locale, then
        // to the tenant's slug/email heuristics, then to 'en'
        // as a last resort. This makes dunning work out of the
        // box for tenants who never opened the preferences card
        // — they get reminders in whatever language the
        // application is currently rendering in.
        $language = $tenant->dunning_language
            ?: app()->getLocale()
            ?: 'en';
        $language = in_array($language, ['ar', 'en'], true) ? $language : 'en';
        $tierDef = DunningConfig::tierDefinitions()[$tierKey] ?? null;
        $daysOverdue = (int) abs(Carbon::today()->diffInDays(Carbon::parse($invoice->due_at)));

        $sentCount = 0;

        foreach ($channels as $channel) {
            $channel = trim((string) $channel);

            // Idempotency pre-check: if we already have a SENT
            // log row for this (invoice, tier, channel) combo, do
            // NOT call dispatch again. Otherwise we'd re-send the
            // email every time the cron runs, defeating the
            // purpose of the unique constraint.
            $alreadySent = DunningReminderLog::query()
                ->where('subscription_invoice_id', $invoice->id)
                ->where('tier_key', $tierKey)
                ->where('channel', $channel)
                ->where('status', DunningReminderLog::STATUS_SENT)
                ->exists();

            if ($alreadySent) {
                // Audit trail: a previous run already sent this
                // tier via this channel. The SENT row already
                // exists in dunning_reminder_logs and the unique
                // constraint would reject any second row for this
                // (invoice, tier, channel) triple — so we
                // deliberately do NOT log a SKIPPED here. The
                // existence of the SENT row IS the audit trail.
                continue;
            }

            try {
                $this->dispatch($channel, $invoice, $tenant, $tierKey, $tierDef, $language, $daysOverdue);
            } catch (Throwable $e) {
                // The channel provider failed (e.g. SMTP down, no
                // email on file). Log it as a failed send and
                // schedule a retry per Phase 7.4 (24h then 72h,
                // then give up).
                $this->logFailureWithRetry(
                    $invoice,
                    $tenant,
                    $tierKey,
                    $channel,
                    $e->getMessage(),
                );
                Log::error('Dunning channel dispatch failed', [
                    'invoice_id' => $invoice->id,
                    'tier' => $tierKey,
                    'channel' => $channel,
                    'error' => $e->getMessage(),
                ]);
                continue;
            }

            // Channel dispatch succeeded — record the SENT log.
            // The unique constraint may still race (two cron runs
            // at the same instant) but logAttemptSafely handles
            // that as a 'duplicate' return value.
            $logged = $this->logAttemptSafely($invoice, $tenant, $tierKey, $channel, DunningReminderLog::STATUS_SENT, null);

            if ($logged === 'inserted') {
                $sentCount++;
            }
        }

        return $sentCount;
    }

    /**
     * Resolve the active DunningConfig for a tenant. Falls back to
     * the global default (tenant_id IS NULL) when no per-tenant
     * override row exists.
     */
    public function configFor(Tenant $tenant): ?DunningConfig
    {
        $override = DunningConfig::query()
            ->where('tenant_id', $tenant->id)
            ->first();

        if ($override) {
            return $override;
        }

        return DunningConfig::query()
            ->whereNull('tenant_id')
            ->first();
    }

    /**
     * Ensure the global default row exists. Called from the
     * install migration and from the cron command's first run.
     */
    public function ensureGlobalDefault(): DunningConfig
    {
        $global = DunningConfig::query()->whereNull('tenant_id')->first();

        if ($global) {
            return $global;
        }

        return DunningConfig::create([
            'tenant_id' => null,
            'enabled' => true,
            'active_tiers' => 'tier_1,tier_2,tier_3,tier_4',
            'channel_matrix' => DunningConfig::defaultChannelMatrix(),
            'message_templates' => null,
        ]);
    }

    /**
     * Build the email subject + body for a given tier. Used by
     * the SubscriptionOverdueMail mailable and (eventually) by
     * the WhatsApp/SMS templates.
     *
     * Returned as ['subject' => ..., 'body' => ...] with the body
     * already in the tenant's preferred language.
     */
    public function buildMessageBody(
        SubscriptionInvoice $invoice,
        string $tierKey,
        string $language,
        int $daysOverdue,
    ): array {
        $tierDef = DunningConfig::tierDefinitions()[$tierKey] ?? null;
        $tone = $tierDef['tone'][$language] ?? ($tierDef['tone']['en'] ?? '');

        $total = number_format((float) $invoice->total, 2);
        $dueDate = Carbon::parse($invoice->due_at)->format('Y-m-d');
        $invoiceNumber = $invoice->invoice_number;
        $tenantName = $invoice->tenant?->name ?: ($language === 'ar' ? 'الإدارة' : 'Billing');
        $daysOverdue = (int) abs(Carbon::today()->diffInDays(Carbon::parse($invoice->due_at)));

        if ($language === 'ar') {
            $subject = match ($tierKey) {
                'tier_1' => "تذكير ودي: فاتورة {$invoiceNumber} مستحقة",
                'tier_2' => "فاتورة {$invoiceNumber} متأخرة — يرجى السداد",
                'tier_3' => 'تنبيه عاجل: تعليق الخدمة خلال 7 أيام',
                'tier_4' => "إشعار نهائي: {$invoiceNumber}",
                default => "فاتورة {$invoiceNumber} متأخرة",
            };

            $body = <<<EOT
السلام عليكم {$tone}،

نود تذكيركم بأن الفاتورة رقم **{$invoiceNumber}** بمبلغ **{$total}** ريال كانت مستحقة بتاريخ **{$dueDate}**، أي منذ **{$daysOverdue}** يوم.

نرجو السداد في أقرب وقت لتجنب تعليق الخدمة.

رقم الفاتورة: {$invoiceNumber}
المبلغ المستحق: {$total} ريال
تاريخ الاستحقاق: {$dueDate}

مع تحيات،
فريق {$tenantName}
EOT;
        } else {
            $subject = match ($tierKey) {
                'tier_1' => "Friendly reminder: invoice {$invoiceNumber} is due",
                'tier_2' => "Invoice {$invoiceNumber} overdue — please pay",
                'tier_3' => 'Urgent: service suspension in 7 days',
                'tier_4' => "Final notice: {$invoiceNumber}",
                default => "Invoice {$invoiceNumber} overdue",
            };

            $body = <<<EOT
Hello {$tone},

This is a reminder that invoice **{$invoiceNumber}** for **{$total}** was due on **{$dueDate}** — {$daysOverdue} days ago.

Please settle the balance to avoid service suspension.

Invoice number: {$invoiceNumber}
Amount due: {$total}
Due date: {$dueDate}

Best regards,
{$tenantName} Team
EOT;
        }

        return ['subject' => $subject, 'body' => $body];
    }

    /**
     * Dispatch the actual send to the right provider.
     */
    private function dispatch(
        string $channel,
        SubscriptionInvoice $invoice,
        Tenant $tenant,
        string $tierKey,
        ?array $tierDef,
        string $language,
        int $daysOverdue,
    ): void {
        $message = $this->buildMessageBody($invoice, $tierKey, $language, $daysOverdue);

        match ($channel) {
            'email' => $this->sendEmail($tenant, $invoice, $tierKey, $message),
            'in_app' => $this->sendInApp($tenant, $invoice, $tierKey, $message),
            'sms' => $this->sendSms($tenant, $invoice, $tierKey, $message),
            'whatsapp' => $this->sendWhatsApp($tenant, $invoice, $tierKey, $message),
            default => throw new InvalidArgumentException("Unknown dunning channel: {$channel}"),
        };
    }

    private function sendEmail(Tenant $tenant, SubscriptionInvoice $invoice, string $tierKey, array $message): void
    {
        if (! $tenant->email) {
            throw new RuntimeException("Tenant #{$tenant->id} has no email on file");
        }

        // Phase 7.1 — attach the invoice PDF so the tenant has
        // a copy in their inbox. We pass the storage path
        // (relative to the public disk) and let the mailable
        // resolve it at send-time. The PDF must already exist
        // (or be generated on the fly here).
        $attachmentPath = $this->resolveInvoiceAttachment($invoice);

        Mail::to($tenant->email)
            ->send(new SubscriptionOverdueMail(
                $invoice,
                $tierKey,
                $message['subject'],
                $message['body'],
                $attachmentPath,
            ));
    }

    /**
     * Get the storage path to the invoice PDF for email
     * attachment, or null if the PDF can't be generated.
     *
     * We use the SubscriptionInvoiceService to generate the
     * PDF on the fly (it's fast — ~1s on dev) so the admin
     * never has to remember to "render PDFs before sending
     * reminders". If the generator throws (e.g. font
     * issue), we log + return null so the email still goes
     * out without an attachment rather than failing the
     * whole dunning send.
     */
    private function resolveInvoiceAttachment(SubscriptionInvoice $invoice): ?string
    {
        try {
            $pdfService = app(SubscriptionInvoiceService::class);
            $relativePath = $pdfService->getPdfPath($invoice);

            if (! $relativePath || ! file_exists($relativePath)) {
                return null;
            }

            // getPdfPath returns an absolute filesystem path;
            // SubscriptionOverdueMail::attachments() wants a
            // path relative to the public disk. Strip the
            // disk root to convert.
            $publicRoot = Storage::disk('public')->path('');

            if (str_starts_with($relativePath, $publicRoot)) {
                return ltrim(substr($relativePath, strlen($publicRoot)), '/');
            }

            return null;
        } catch (Throwable $e) {
            Log::warning('Dunning: failed to resolve invoice PDF for attachment', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function sendInApp(Tenant $tenant, SubscriptionInvoice $invoice, string $tierKey, array $message): void
    {
        // Notify ALL users of the tenant, not just one — the owner
        // and any admins they delegated billing to should see the
        // reminder. We use InternalNotification so it shows up in
        // the bell icon. action_url points to the billing page.
        $users = $tenant->users()->get(['users.id']);
        $userIds = $users->pluck('id')->all();

        if (empty($userIds)) {
            return; // no users to notify — skip silently
        }

        foreach ($userIds as $userId) {
            NotificationService::notify(
                tenantId: $tenant->id,
                userId: $userId,
                type: 'billing.dunning.'.$tierKey,
                title: $message['subject'],
                body: $message['body'],
                actionUrl: '/app/billing',
                icon: 'bell',
                data: [
                    'invoice_id' => $invoice->id,
                    'tier' => $tierKey,
                ],
            );
        }
    }

    private function sendSms(Tenant $tenant, SubscriptionInvoice $invoice, string $tierKey, array $message): void
    {
        if (! $tenant->phone) {
            throw new RuntimeException("Tenant #{$tenant->id} has no phone on file");
        }

        // Lazy-load SmsService to keep dunning dispatchable even
        // when the SMS package isn't installed. We catch the
        // failure at the caller and log it as a failure row.
        $sms = app(SmsService::class);
        $sms->send($tenant->phone, $message['body']);
    }

    private function sendWhatsApp(Tenant $tenant, SubscriptionInvoice $invoice, string $tierKey, array $message): void
    {
        if (! $tenant->phone) {
            throw new RuntimeException("Tenant #{$tenant->id} has no phone on file");
        }

        // WhatsApp gateway comes from the tenant's integration
        // settings. For Phase 6 we just throw if no integration is
        // configured; the dispatcher will mark this as failed and
        // continue to the next channel.
        $sms = app(SmsService::class);
        $sms->sendWhatsApp($tenant->phone, $message['body']);
    }

    /**
     * Write a log row, gracefully handling the unique-constraint
     * case (already-sent). Returns:
     *   'inserted'  — the row was created
     *   'duplicate' — the row already exists (we hit the unique
     *                 key on invoice_id+tier_key+channel)
     *
     * We split this from the broader try/catch so the SKIPPED log
     * for a duplicate SENT does not itself collide with the
     * existing SENT row (which is what bit us in early testing —
     * the SKIPPED insert failed with the same constraint).
     */
    private function logAttemptSafely(
        SubscriptionInvoice $invoice,
        Tenant $tenant,
        string $tierKey,
        string $channel,
        string $status,
        ?string $error,
    ): string {
        try {
            DunningReminderLog::create([
                'tenant_id' => $tenant->id,
                'subscription_invoice_id' => $invoice->id,
                'tier_key' => $tierKey,
                'channel' => $channel,
                'status' => $status,
                'error_message' => $error,
                'sent_at' => now(),
            ]);

            return 'inserted';
        } catch (QueryException $e) {
            // SQLSTATE 23000 = integrity constraint violation.
            // On the unique key this means the row already
            // exists — the previous run already logged the SENT.
            if ((string) $e->getCode() === '23000') {
                return 'duplicate';
            }

            throw $e;
        }
    }

    /**
     * Phase 7.4 — log a failed send + schedule a retry.
     *
     * If this is the first time we've failed on this (invoice,
     * tier, channel) combo, INSERT a new row with retry_count=0
     * and next_retry_at = +24h. If a FAILED row already exists
     * (from a previous attempt that hit the same failure),
     * UPDATE it to bump retry_count and roll next_retry_at
     * forward by another 24h or 72h depending on the count.
     *
     * After 2 retries the row is left with next_retry_at=null
     * and the dispatcher will skip it on the next cron run.
     */
    private function logFailureWithRetry(
        SubscriptionInvoice $invoice,
        Tenant $tenant,
        string $tierKey,
        string $channel,
        string $error,
    ): void {
        $existing = DunningReminderLog::query()
            ->where('subscription_invoice_id', $invoice->id)
            ->where('tier_key', $tierKey)
            ->where('channel', $channel)
            ->where('status', DunningReminderLog::STATUS_FAILED)
            ->first();

        $retryService = app(DunningRetryService::class);

        if ($existing) {
            $newRetryCount = $existing->retry_count + 1;
            $nextRetryAt = $retryService->nextRetryAt($newRetryCount);

            $existing->update([
                'retry_count' => $newRetryCount,
                'next_retry_at' => $nextRetryAt,
                'error_message' => $error,
                'sent_at' => now(),
            ]);
        } else {
            $nextRetryAt = $retryService->nextRetryAt(0);

            DunningReminderLog::create([
                'tenant_id' => $tenant->id,
                'subscription_invoice_id' => $invoice->id,
                'tier_key' => $tierKey,
                'channel' => $channel,
                'status' => DunningReminderLog::STATUS_FAILED,
                'error_message' => $error,
                'sent_at' => now(),
                'retry_count' => 0,
                'next_retry_at' => $nextRetryAt,
            ]);
        }
    }

    /**
     * Phase 7.3 — check whether the current local time falls
     * inside the configured quiet hours window. The window
     * always crosses midnight (e.g. 21:00 → 08:00 next day),
     * so the comparison has to handle the wraparound.
     *
     * Returns false (= "do not skip, send normally") when:
     *  - quiet_hours_start or quiet_hours_end is null (admin
     *    chose to disable quiet hours)
     *  - the start equals the end (zero-width window, treated
     *    as "no quiet hours")
     *  - the current time is outside the window
     *
     * Returns true (= "skip this send") otherwise.
     */
    public function isInQuietHours(DunningConfig $config): bool
    {
        $start = $config->quiet_hours_start;
        $end = $config->quiet_hours_end;

        if (! $start || ! $end) {
            return false;
        }

        $startMinutes = (int) $start->format('H') * 60 + (int) $start->format('i');
        $endMinutes = (int) $end->format('H') * 60 + (int) $end->format('i');
        $nowMinutes = (int) now()->format('H') * 60 + (int) now()->format('i');

        // Zero-width window — never skip
        if ($startMinutes === $endMinutes) {
            return false;
        }

        // Two cases:
        //  1. start < end (window fits within a single day,
        //     e.g. 13:00 → 17:00) — straightforward comparison
        //  2. start > end (window crosses midnight, e.g.
        //     21:00 → 08:00) — we're inside if now >= start OR
        //     now < end
        if ($startMinutes < $endMinutes) {
            return $nowMinutes >= $startMinutes && $nowMinutes < $endMinutes;
        }

        return $nowMinutes >= $startMinutes || $nowMinutes < $endMinutes;
    }
}
