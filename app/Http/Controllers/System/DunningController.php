<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\Billing\DunningConfig;
use App\Models\Billing\DunningReminderLog;
use App\Models\Tenant;
use App\Services\Billing\DunningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin endpoints for the dunning automation (Phase 6).
 *
 * Two pages:
 *  - /system/settings/dunning        — global config + tier preview
 *  - /system/billing/dunning-log     — full audit trail of sends
 *
 * Plus two POST endpoints for the admin to update config and
 * to manually trigger a single dry-run send (useful for testing
 * templates against a fake invoice).
 */
class DunningController extends Controller
{
    public function __construct(private readonly DunningService $dunning) {}

    /**
     * Settings page — shows the global config, the tier table, and
     * lets the admin toggle enabled/active tiers and the channel
     * matrix. Per-tenant overrides are managed from the tenant
     * detail page (future Phase).
     */
    public function settings(): Response
    {
        $global = $this->dunning->ensureGlobalDefault();

        return Inertia::render('System/Settings/DunningSettings', [
            'config' => [
                'enabled' => $global->enabled,
                'active_tiers' => $global->active_tiers,
                'channel_matrix' => $global->channel_matrix ?: DunningConfig::defaultChannelMatrix(),
                'quiet_hours_start' => $global->quiet_hours_start ? $global->quiet_hours_start->format('H:i') : null,
                'quiet_hours_end' => $global->quiet_hours_end ? $global->quiet_hours_end->format('H:i') : null,
                'auto_suspend_after_days' => $global->auto_suspend_after_days,
            ],
            'tier_definitions' => DunningConfig::tierDefinitions(),
            'default_channel_matrix' => DunningConfig::defaultChannelMatrix(),
        ]);
    }

    /**
     * Persist the global config. Per-tenant overrides use a
     * separate endpoint (or the tenant detail page) so this stays
     * a single-row edit and we don't have to deal with the
     * "create vs update" branching.
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
            'active_tiers' => ['required', 'string', 'max:100'],
            'channel_matrix' => ['required', 'array'],
            'channel_matrix.tier_1' => ['array'],
            'channel_matrix.tier_2' => ['array'],
            'channel_matrix.tier_3' => ['array'],
            'channel_matrix.tier_4' => ['array'],
            'quiet_hours_start' => ['nullable', 'date_format:H:i'],
            'quiet_hours_end' => ['nullable', 'date_format:H:i'],
            'auto_suspend_after_days' => ['nullable', 'integer', 'min:1', 'max:90'],
        ]);

        $global = $this->dunning->ensureGlobalDefault();
        $global->update([
            'enabled' => $validated['enabled'],
            'active_tiers' => $validated['active_tiers'],
            'channel_matrix' => $validated['channel_matrix'],
            'quiet_hours_start' => $validated['quiet_hours_start'] ?? null,
            'quiet_hours_end' => $validated['quiet_hours_end'] ?? null,
            'auto_suspend_after_days' => $validated['auto_suspend_after_days'] ?? null,
        ]);

        return redirect()
            ->route('system.settings.dunning')
            ->with('success', __('Dunning settings updated.'));
    }

    /**
     * Dunning log — paginated audit trail of all reminder
     * attempts. Each row shows tenant, invoice, tier, channel,
     * status, and any error message. Filters are simple query
     * params: ?status=sent|failed|skipped, ?tier=tier_1, ?tenant_id.
     */
    public function log(Request $request): Response
    {
        $query = DunningReminderLog::query()
            // The invoices table has no `total` and no `due_at` column: the
            // amount is `total_incl_tax` and the date is `due_date`. Asking
            // for columns that do not exist is a fatal SQL error.
            ->with(['tenant:id,name', 'invoice:id,invoice_number,total_incl_tax,due_date'])
            ->orderByDesc('sent_at');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($tier = $request->query('tier')) {
            $query->where('tier_key', $tier);
        }

        if ($tenantId = $request->query('tenant_id')) {
            $query->where('tenant_id', (int) $tenantId);
        }

        $logs = $query->paginate(50)->withQueryString();

        return Inertia::render('System/Settings/DunningLog', [
            'logs' => $logs,
            'filters' => [
                'status' => $status,
                'tier' => $tier,
                'tenant_id' => $tenantId,
            ],
            'tier_definitions' => DunningConfig::tierDefinitions(),
        ]);
    }

    /**
     * Phase 7.5 — per-tenant dunning config page. The admin
     * picks a tenant, sees the global dunning config as a
     * baseline, and overrides what they need. Empty fields
     * mean "fall back to the global default".
     */
    public function tenantConfig(Tenant $tenant): Response
    {
        $global = DunningConfig::first() ?? DunningConfig::create([]);
        $override = DunningConfig::query()
            ->where('tenant_id', $tenant->id)
            ->first();

        return Inertia::render('System/Settings/TenantDunning', [
            'tenant' => $tenant->only(['id', 'name', 'email']),
            'global_config' => [
                'enabled' => $global->enabled,
                'active_tiers' => $global->active_tiers,
                'channel_matrix' => $global->channel_matrix ?: DunningConfig::defaultChannelMatrix(),
                'quiet_hours_start' => $global->quiet_hours_start ? $global->quiet_hours_start->format('H:i') : null,
                'quiet_hours_end' => $global->quiet_hours_end ? $global->quiet_hours_end->format('H:i') : null,
            ],
            'override' => $override ? [
                'enabled' => $override->enabled,
                'active_tiers' => $override->active_tiers,
                'channel_matrix' => $override->channel_matrix ?: DunningConfig::defaultChannelMatrix(),
                'quiet_hours_start' => $override->quiet_hours_start ? $override->quiet_hours_start->format('H:i') : null,
                'quiet_hours_end' => $override->quiet_hours_end ? $override->quiet_hours_end->format('H:i') : null,
            ] : null,
        ]);
    }

    /**
     * Phase 7.5 — save or create the override row for a tenant.
     */
    public function updateTenantConfig(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
            'active_tiers' => ['required', 'string', 'max:100'],
            'channel_matrix' => ['required', 'array'],
            'channel_matrix.tier_1' => ['array'],
            'channel_matrix.tier_2' => ['array'],
            'channel_matrix.tier_3' => ['array'],
            'channel_matrix.tier_4' => ['array'],
            'quiet_hours_start' => ['nullable', 'date_format:H:i'],
            'quiet_hours_end' => ['nullable', 'date_format:H:i'],
        ]);

        DunningConfig::updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'enabled' => $validated['enabled'],
                'active_tiers' => $validated['active_tiers'],
                'channel_matrix' => $validated['channel_matrix'],
                'quiet_hours_start' => $validated['quiet_hours_start'] ?? null,
                'quiet_hours_end' => $validated['quiet_hours_end'] ?? null,
            ]
        );

        return back()->with('success', __('messages.system.dunning.tenant_saved'));
    }

    /**
     * Phase 7.5 — drop the override row so the tenant falls
     * back to the global config.
     */
    public function clearTenantConfig(Tenant $tenant)
    {
        DunningConfig::query()->where('tenant_id', $tenant->id)->delete();

        return back()->with('success', __('messages.system.dunning.tenant_cleared'));
    }
}
