<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use Database\Seeders\PerformanceBaselineSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Probes the query count of the endpoints whose perf budget is
 * failing, to tell a hidden N+1 apart from a fixed overhead.
 *
 * THE QUESTION
 *
 * Three endpoints are one query over their budget. Two of them sit
 * exactly on the limit: `assertLessThan(25, ...)` with 25 queries.
 * The file already records that shape once — the vehicles endpoint
 * was raised from < 25 to < 30 after it was measured at 25, with the
 * note that the endpoint "used 25 queries and the assertion was
 * off by exactly one".
 *
 * Before a budget is raised it has to be shown that the count is
 * FIXED. A count that grows with the data is an N+1 and raising the
 * budget hides it; a count that does not grow is overhead, and the
 * assertion is what is wrong.
 *
 * This probe measures the count at two data sizes and asserts that
 * it does not grow.
 */
class PerfQueryShapeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        @ini_set('memory_limit', '512M');
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        $this->actingAs($user);
    }

    /**
     * The endpoints under suspicion, with the number of records the
     * probe grows.
     */
    private const ENDPOINTS = [
        '/app/work-orders/1' => 'work order detail',
        '/app/inventory/stock' => 'inventory stock list',
        '/app/workshop' => 'workshop board',
    ];

    public function test_the_failing_endpoints_do_not_scale_with_the_data(): void
    {
        $report = [];

        foreach (array_keys(self::ENDPOINTS) as $url) {
            $before = $this->countQueries($url);
            $report[$url] = ['small' => $before];

            $this->growTheData();

            $after = $this->countQueries($url);
            $report[$url]['large'] = $after;
            $report[$url]['delta'] = $after - $before;
        }

        fwrite(STDERR, "\n[QUERY SHAPE] ".json_encode($report, JSON_PRETTY_PRINT)."\n");

        foreach ($report as $url => $shape) {
            $this->assertLessThanOrEqual(
                2,
                $shape['delta'],
                "{$url} runs {$shape['delta']} more queries when the data grows. "
                .'That is an N+1 — raising the budget would hide it.'
            );
        }
    }

    /**
     * The number of queries one request runs.
     */
    private function countQueries(string $url): int
    {
        $this->get($url);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get($url);
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    /**
     * Add enough records for a second page to be worth fetching.
     */
    private function growTheData(): void
    {
        $tenant = Tenant::first();
        $center = Center::first();
        $user = User::first();

        if (! $tenant || ! $center || ! $user) {
            return;
        }

        Vehicle::factory()->count(30)->create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
        ]);

        WorkOrder::factory()->count(30)->create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => Customer::first()?->id,
            'vehicle_id' => Vehicle::first()?->id,
        ]);
    }
}
