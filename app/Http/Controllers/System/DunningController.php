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
        ]);

        $global = $this->dunning->ensureGlobalDefault();
        $global->update([
            'enabled' => $validated['enabled'],
            'active_tiers' => $validated['active_tiers'],
            'channel_matrix' => $validated['channel_matrix'],
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
            ->with(['tenant:id,name', 'invoice:id,invoice_number,total,due_at'])
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
}
