<?php

declare(strict_types=1);

namespace App\Services\HR;

use App\Models\HR\Employee;
use App\Models\WorkOrderItem;
use App\Models\WorkOrderLaborEntry;
use Carbon\Carbon;

class TechnicianCommissionService
{
    /**
     * Calculate technician productivity KPIs and commission for a payroll period.
     *
     * @return array{
     *     commission_amount: float,
     *     billable_items_count: int,
     *     warranty_items_count: int,
     *     billable_revenue: float,
     *     first_time_fix_rate: float,
     *     estimated_hours: float,
     *     actual_hours: float,
     *     efficiency_rate: float|null
     * }
     */
    public function calculateForPeriod(Employee $employee, Carbon $periodStart, Carbon $periodEnd): array
    {
        if (! $employee->user_id) {
            return $this->emptyMetrics();
        }

        $start = $periodStart->copy()->startOfDay();
        $end = $periodEnd->copy()->endOfDay();
        $userId = (int) $employee->user_id;

        $completedItems = WorkOrderItem::withoutGlobalScopes()
            ->where('tenant_id', $employee->tenant_id)
            ->where('center_id', $employee->center_id)
            ->where('status', WorkOrderItem::STATUS_COMPLETED)
            ->whereBetween('completed_at', [$start, $end])
            ->whereHas('technicians', fn ($q) => $q->where('users.id', $userId))
            ->withCount('technicians')
            ->get([
                'id',
                'is_warranty',
                'line_total_excl_tax',
                'line_total',
                'total',
                'estimated_duration',
                'actual_duration',
            ]);

        $billableItems = $completedItems->where('is_warranty', false);
        $warrantyItems = $completedItems->where('is_warranty', true);

        $billableCount = $billableItems->count();
        $warrantyCount = $warrantyItems->count();
        $totalCount = $billableCount + $warrantyCount;

        $billableRevenue = 0.0;

        foreach ($billableItems as $item) {
            $itemTotal = (float) ($item->line_total_excl_tax ?? $item->line_total ?? $item->total ?? 0);
            $techCount = max(1, (int) ($item->technicians_count ?? 1));
            $billableRevenue += ($itemTotal / $techCount);
        }
        $billableRevenue = round($billableRevenue, 2);

        $firstTimeFixRate = $totalCount > 0
            ? round(($billableCount / $totalCount) * 100, 1)
            : 100.0;

        $estimatedMinutes = (float) $completedItems->sum('estimated_duration');
        $laborSeconds = (float) WorkOrderLaborEntry::withoutGlobalScopes()
            ->where('tenant_id', $employee->tenant_id)
            ->where('center_id', $employee->center_id)
            ->where('user_id', $userId)
            ->whereBetween('started_at', [$start, $end])
            ->get()
            ->sum(fn (WorkOrderLaborEntry $entry) => $entry->effectiveDurationSeconds());

        $actualMinutes = $laborSeconds > 0
            ? ($laborSeconds / 60.0)
            : (float) $completedItems->sum('actual_duration');

        $estimatedHours = round($estimatedMinutes / 60.0, 2);
        $actualHours = round($actualMinutes / 60.0, 2);

        $efficiencyRate = ($estimatedMinutes > 0 && $actualMinutes > 0)
            ? round(($estimatedMinutes / $actualMinutes) * 100, 1)
            : null;

        $commissionAmount = 0.0;
        $rate = (float) ($employee->commission_rate ?? 0);

        if ($employee->commission_enabled && $rate > 0 && $billableCount > 0) {
            $commissionAmount = $employee->commission_type === 'fixed'
                ? round($billableCount * $rate, 2)
                : round($billableRevenue * ($rate / 100.0), 2);
        }

        return [
            'commission_amount' => $commissionAmount,
            'billable_items_count' => $billableCount,
            'warranty_items_count' => $warrantyCount,
            'billable_revenue' => $billableRevenue,
            'first_time_fix_rate' => $firstTimeFixRate,
            'estimated_hours' => $estimatedHours,
            'actual_hours' => $actualHours,
            'efficiency_rate' => $efficiencyRate,
        ];
    }

    private function emptyMetrics(): array
    {
        return [
            'commission_amount' => 0.0,
            'billable_items_count' => 0,
            'warranty_items_count' => 0,
            'billable_revenue' => 0.0,
            'first_time_fix_rate' => 100.0,
            'estimated_hours' => 0.0,
            'actual_hours' => 0.0,
            'efficiency_rate' => null,
        ];
    }
}
