<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\TaxPeriod;
use App\Models\Tenant;
use Illuminate\Console\Command;

class ReviewLegacyTaxPeriods extends Command
{
    protected $signature = 'tax-periods:review-legacy {tenant : Tenant ID to review without modifying records}';

    protected $description = 'Report historical branch tax periods without merging or changing them';

    public function handle(): int
    {
        $tenantId = filter_var($this->argument('tenant'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($tenantId === false || ! Tenant::query()->whereKey($tenantId)->exists()) {
            $this->error(__('reports.tax_periods_report.review_tenant_missing'));

            return self::FAILURE;
        }
        $periods = TaxPeriod::withoutGlobalScope('tenant_scoped')
            ->where('tenant_id', $tenantId)->whereNotNull('center_id')
            ->withCount('transactions')->orderBy('period_key')->orderBy('id')->get();
        $this->line(json_encode([
            'tenant_id' => $tenantId,
            'read_only' => true,
            'legacy_period_count' => $periods->count(),
            'periods' => $periods->map(fn (TaxPeriod $period) => $period->only(['id', 'center_id', 'period_key', 'status', 'transactions_count']))->all(),
        ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }
}
