<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\Billing\DunningConfig;
use App\Models\Tenant;
use App\Services\Billing\DunningService;
use App\Services\System\SystemAuditService;
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
    private SystemAuditService $audit;

    public function __construct(
        private readonly DunningService $dunning,
        ?SystemAuditService $audit = null
    ) {
        $this->audit = $audit ?? app(SystemAuditService::class);
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

        $this->audit->log('dunning_global_settings_update', DunningConfig::class, $global->id, [
            'enabled' => $validated['enabled'],
            'active_tiers' => $validated['active_tiers'],
            'auto_suspend_after_days' => $validated['auto_suspend_after_days'] ?? null,
        ], $request);

        return redirect()
            ->route('system.settings.dunning')
            ->with('success', __('Dunning settings updated.'));
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

        $config = DunningConfig::updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'enabled' => $validated['enabled'],
                'active_tiers' => $validated['active_tiers'],
                'channel_matrix' => $validated['channel_matrix'],
                'quiet_hours_start' => $validated['quiet_hours_start'] ?? null,
                'quiet_hours_end' => $validated['quiet_hours_end'] ?? null,
            ]
        );

        $this->audit->log('dunning_tenant_config_update', DunningConfig::class, $config->id, [
            'tenant_id' => $tenant->id,
            'enabled' => $validated['enabled'],
            'active_tiers' => $validated['active_tiers'],
        ], $request);

        return back()->with('success', __('messages.system.dunning.tenant_saved'));
    }

    /**
     * Phase 7.5 — drop the override row so the tenant falls
     * back to the global config.
     */
    public function clearTenantConfig(Tenant $tenant)
    {
        DunningConfig::query()->where('tenant_id', $tenant->id)->delete();

        $this->audit->log('dunning_tenant_config_clear', Tenant::class, $tenant->id);

        return back()->with('success', __('messages.system.dunning.tenant_cleared'));
    }
}
