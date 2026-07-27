<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Models\Billing\DunningConfig;
use App\Models\Billing\DunningReminderLog;
use App\Models\Billing\SubscriptionInvoice;
use App\Models\Tenant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Phase 7.7 — Auto-suspend tenants whose last `tier_4` dunning
 * reminder was sent more than N days ago and the underlying invoice
 * is still unpaid.
 *
 * The intent: `SendDunningReminders` already sent the final notice
 * (tier_4) at 30+ days overdue. If the tenant STILL hasn't paid
 * after another grace period (default 7 days), suspend the account
 * automatically so the admin doesn't have to chase it manually.
 *
 * Idempotency: a tenant is only suspended once per unpaid-invoice
 * cycle. We use the `auto_suspended_at` column as the marker — if
 * it's set AND the invoice is still unpaid, we don't re-suspend
 * (we'd just be resetting the clock on the admin's view). If the
 * invoice is paid, the marker is cleared on the next run.
 */
class TenantSuspensionService
{
    /**
     * Suspension reason sentinel — used as `suspension_reason` value
     * and as an exact-match filter when admins want to find all
     * auto-suspended tenants.
     */
    public const REASON = 'auto_suspend_dunning_tier_4';

    /**
     * Find all tenants eligible for auto-suspension, suspend them,
     * return a summary.
     *
     * @return array{suspended: int, skipped_paid: int, skipped_disabled: int, errors: int}
     */
    public function runForAllTenants(): array
    {
        $summary = [
            'suspended' => 0,
            'skipped_paid' => 0,
            'skipped_disabled' => 0,
            'errors' => 0,
        ];

        $global = $this->globalAutoSuspendDays();

        if ($global === null) {
            $summary['skipped_disabled'] = 1;

            return $summary;
        }

        // Find the LATEST tier_4 log per (tenant_id, invoice_id) that
        // is older than the grace window. We group by invoice so a
        // single tenant with multiple overdue invoices only counts
        // each invoice once.
        $cutoff = Carbon::now()->subDays($global);

        $logs = DunningReminderLog::query()
            ->where('tier_key', 'tier_4')
            ->where('sent_at', '<=', $cutoff)
            ->where('status', DunningReminderLog::STATUS_SENT)
            ->orderByDesc('sent_at')
            ->get();

        // Group by tenant_id + invoice_id, keep the newest log
        $seen = [];

        foreach ($logs as $log) {
            $key = $log->tenant_id.':'.$log->invoice_id;

            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = $log;

            try {
                $result = $this->suspendIfStillOverdue($log);

                if ($result === 'suspended') {
                    $summary['suspended']++;
                } elseif ($result === 'paid') {
                    $summary['skipped_paid']++;
                }
            } catch (Throwable $e) {
                $summary['errors']++;
                Log::warning('dunning.auto_suspend.error', [
                    'tenant_id' => $log->tenant_id,
                    'invoice_id' => $log->invoice_id,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return $summary;
    }

    /**
     * Try to suspend one tenant based on a tier_4 log. Returns
     * 'suspended' | 'paid' | 'already' | 'opted_out' | 'no_invoice'.
     */
    public function suspendIfStillOverdue(DunningReminderLog $log): string
    {
        $invoice = SubscriptionInvoice::query()->find($log->subscription_invoice_id);

        if (! $invoice) {
            return 'no_invoice';
        }

        if ($this->isInvoicePaid($invoice)) {
            return 'paid';
        }

        $tenant = Tenant::query()->find($log->tenant_id);

        if (! $tenant) {
            return 'no_invoice';
        }

        if ($tenant->dunning_opted_out) {
            return 'opted_out';
        }

        // Already auto-suspended for THIS cycle? Skip — but if the
        // tenant is currently manually suspended, do nothing (admin
        // is handling it).
        if ($tenant->auto_suspended_at !== null) {
            return 'already';
        }

        return $this->suspend($tenant, $invoice) ? 'suspended' : 'already';
    }

    /**
     * The actual suspend — sets both `suspended_at` and
     * `auto_suspended_at` so the admin can distinguish.
     */
    protected function suspend(Tenant $tenant, SubscriptionInvoice $invoice): bool
    {
        $now = Carbon::now();

        $tenant->forceFill([
            'suspended_at' => $now,
            'auto_suspended_at' => $now,
            'suspension_reason' => self::REASON,
        ])->save();

        Log::info('dunning.auto_suspend.applied', [
            'tenant_id' => $tenant->id,
            'tenant_name' => $tenant->name,
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->number,
        ]);

        return true;
    }

    /**
     * Clear auto-suspend marker when the invoice is paid so a new
     * tier_4 cycle in the future can suspend the tenant again if
     * they lapse a second time. Manual suspension is NOT cleared.
     */
    public function clearIfPaid(Tenant $tenant): void
    {
        if ($tenant->auto_suspended_at === null) {
            return;
        }

        if ($tenant->suspension_reason !== self::REASON) {
            return;
        }

        $tenant->forceFill([
            'suspended_at' => null,
            'auto_suspended_at' => null,
            'suspension_reason' => null,
        ])->save();
    }

    protected function isInvoicePaid(SubscriptionInvoice $invoice): bool
    {
        // Mirrors the same check SendDunningReminders uses — see
        // DunningService::findCandidates() at line 86. Any
        // non-'pending' status is treated as "handled" and the
        // tenant should NOT be auto-suspended.
        return $invoice->status !== 'pending';
    }

    protected function globalAutoSuspendDays(): ?int
    {
        $row = DunningConfig::query()
            ->whereNull('tenant_id')
            ->value('auto_suspend_after_days');

        return $row === null ? null : (int) $row;
    }
}
