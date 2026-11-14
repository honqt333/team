<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Reports\Concerns;

use App\Models\Center;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Throwable;

trait ReportScopingTrait
{
    /**
     * Resolve the center IDs the signed-in user is allowed to access.
     * System admins and tenant super-admins have access to ALL centres,
     * so they are not limited. Every other account is limited to the
     * centres it is assigned to — the same rule the employee list uses.
     *
     * @return array<int>|null null means "every centre of the tenant"
     */
    protected function accessibleCenterIds(): ?array
    {
        $user = auth()->user();

        if (! $user) {
            return null;
        }

        if ($user->is_system_admin || $user->hasRole('super_admin')) {
            return null;
        }

        $centerIds = $user->centers()
            ->pluck('centers.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($centerIds === []) {
            if ($user->current_center_id) {
                return [(int) $user->current_center_id];
            }

            return null;
        }

        return array_values(array_unique($centerIds));
    }

    /**
     * Resolve the center filter:
     * - Explicit center request parameter takes precedence
     * - Defaults to currently active center context (TenancyContext::centerId())
     * - Falls back to 'all' if no center context exists
     */
    protected function resolveCenterFilter(Request $request, ?int $activeCenterId): string
    {
        $requested = null;

        if ($request->has('center_id') && $request->input('center_id') !== null && $request->input('center_id') !== '') {
            $requested = (string) $request->input('center_id');
        } elseif ($activeCenterId !== null) {
            $requested = (string) $activeCenterId;
        } else {
            $requested = 'all';
        }

        $accessibleCenterIds = $this->accessibleCenterIds();

        if ($accessibleCenterIds !== null) {
            if ($requested !== 'all' && is_numeric($requested) && in_array((int) $requested, $accessibleCenterIds, true)) {
                return (string) ((int) $requested);
            }

            if ($activeCenterId !== null && in_array($activeCenterId, $accessibleCenterIds, true)) {
                return (string) $activeCenterId;
            }

            return $accessibleCenterIds !== [] ? (string) $accessibleCenterIds[0] : '0';
        }

        return $requested;
    }

    /**
     * Resolve the collection of centers accessible to the signed-in user.
     */
    protected function resolveAccessibleCenters(int $tenantId)
    {
        $accessibleCenterIds = $this->accessibleCenterIds();

        return Center::query()
            ->where('tenant_id', $tenantId)
            ->when($accessibleCenterIds !== null, fn ($q) => $q->whereIn('id', $accessibleCenterIds))
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
    }

    /**
     * Resolve company info for reports, aligning branch name to filtered/current center.
     */
    protected function resolveReportCompanyInfo(Tenant $tenant, string $centerFilter, ?int $activeCenterId): array
    {
        $targetCenterId = ($centerFilter !== 'all' && is_numeric($centerFilter))
            ? (int) $centerFilter
            : $activeCenterId;

        $targetCenter = $targetCenterId ? Center::find($targetCenterId) : null;

        return [
            'name' => $tenant->legal_name ?: $tenant->name,
            'commercial_register' => $tenant->cr_number ?? null,
            'cr_number' => $tenant->cr_number ?? '',
            'tax_number' => $tenant->taxSettings->tax_number ?? $tenant->tax_number ?? $tenant->vat_number ?? null,
            'vat_number' => $tenant->taxSettings->tax_number ?? $tenant->tax_number ?? $tenant->vat_number ?? '',
            'address' => $tenant->address ?? null,
            'phone' => $tenant->phone ?? null,
            'logo_path' => $tenant->logo_path ?? '',
            'center_name' => $targetCenter->name ?? '',
        ];
    }

    /**
     * Resolve date range presets.
     */
    protected function resolveDateRange(?string $range = 'this_month', ?string $start = null, ?string $end = null): array
    {
        $now = Carbon::now();
        $range = $range ?: 'this_month';

        if ($range === 'all' || $range === 'all_time') {
            return [null, null];
        }

        if ($range === 'custom') {
            $parsedStart = null;
            $parsedEnd = null;

            if (! empty($start)) {
                try {
                    $parsedStart = Carbon::parse($start)->startOfDay();
                } catch (Throwable) {
                    $parsedStart = null;
                }
            }

            if (! empty($end)) {
                try {
                    $parsedEnd = Carbon::parse($end)->endOfDay();
                } catch (Throwable) {
                    $parsedEnd = null;
                }
            }

            return [$parsedStart, $parsedEnd];
        }

        return match ($range) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'yesterday' => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay()],
            'this_week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'this_month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'this_year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            'all', 'all_time' => [null, null],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
        };
    }
}
