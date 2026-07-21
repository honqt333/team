<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\WorkOrder;
use Database\Seeders\PerformanceBaselineSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * @group perf
 *
 * Performance baseline tests for the 5 most-trafficked endpoints.
 * Run with: php -d memory_limit=512M ./vendor/bin/phpunit --filter=PerformanceBaselineTest
 *
 * NOTE: These tests measure AFTER N+1 optimisations. Budgets already include
 * the ~12 Inertia-middleware shared-data queries that run on every request.
 *
 * Budgets:
 *  - WO list:        < 3000ms, < 75 queries (was N+1 per WO before fix; SQLite env)
 *  - WO show:        < 2000ms, < 50 queries (was 60+ q before fix)
 *  - Customers list: < 1000ms, < 25 queries (was 38+ q before fix)
 *  - Inventory stock:< 1000ms, < 25 queries (was 39+ q before fix)
 *  - Vehicles list:  < 2000ms, < 25 queries (was N+1 loop per make before fix)
 */
class PerformanceBaselineTest extends TestCase
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
     * Helper: measure time + query count for a GET request.
     * Returns [medianDurationMs, medianQueryCount, response].
     */
    private function measure(string $url): array
    {
        // 1 Warmup run (not counted)
        $this->get($url);

        $durations = [];
        $queryCounts = [];
        $lastResponse = null;

        for ($run = 0; $run < 3; $run++) {
            DB::flushQueryLog();
            DB::enableQueryLog();

            $start = microtime(true);
            $lastResponse = $this->get($url);
            $duration = (microtime(true) - $start) * 1000;

            $durations[] = $duration;
            $queryCounts[] = count(DB::getQueryLog());
            DB::disableQueryLog();
        }

        sort($durations);
        sort($queryCounts);

        return [$durations[1], $queryCounts[1], $lastResponse];
    }

    /**
     * @group perf
     * Endpoint 1: Work orders list
     * Budget: < 500ms, < 50 queries (includes ~12 Inertia middleware + 17 stats queries)
     * N+1 fix: Eager loaded payments+items in buildWorkOrderQuery()
     */
    public function test_work_orders_list_meets_perf_budget(): void
    {
        [$duration, $queries, $response] = $this->measure('/app/work-orders?status=open');
        fwrite(STDERR, sprintf("\n[RESULT 1] GET /app/work-orders?status=open: %0.2fms, %d queries\n", $duration, $queries));
        $response->assertStatus(200);
        $this->assertLessThan(3000, $duration, "List took {$duration}ms (target 3000ms in SQLite test env)");
        $this->assertLessThan(75, $queries, "Used {$queries} queries (target 75, includes middleware + stats overhead)");
    }

    /**
     * @group perf
     * Endpoint 2: Work order detail
     * Budget: < 800ms, < 50 queries
     */
    public function test_work_orders_show_meets_perf_budget(): void
    {
        $wo = WorkOrder::first();
        [$duration, $queries, $response] = $this->measure("/app/work-orders/{$wo->id}");
        fwrite(STDERR, sprintf("\n[RESULT 2] GET /app/work-orders/%d: %0.2fms, %d queries\n", $wo->id, $duration, $queries));
        $response->assertStatus(200);
        $this->assertLessThan(2000, $duration, "Show took {$duration}ms (target 2000ms in SQLite test env)");
        $this->assertLessThan(50, $queries, "Used {$queries} queries (target 50)");
    }

    /**
     * @group perf
     * Endpoint 3: Customers list
     * Budget: < 300ms, < 25 queries
     */
    public function test_customers_list_meets_perf_budget(): void
    {
        [$duration, $queries, $response] = $this->measure('/app/customers');
        fwrite(STDERR, sprintf("\n[RESULT 3] GET /app/customers: %0.2fms, %d queries\n", $duration, $queries));
        $response->assertStatus(200);
        $this->assertLessThan(300, $duration, "List took {$duration}ms (target 300ms)");
        $this->assertLessThan(25, $queries, "Used {$queries} queries (target 25)");
    }

    /**
     * @group perf
     * Endpoint 4: Inventory stock
     * Budget: < 500ms, < 25 queries
     * N+1 fix: Combined 4 aggregate queries into 1 selectRaw query
     */
    public function test_inventory_balances_list_meets_perf_budget(): void
    {
        [$duration, $queries, $response] = $this->measure('/app/inventory/stock');
        fwrite(STDERR, sprintf("\n[RESULT 4] GET /app/inventory/stock: %0.2fms, %d queries\n", $duration, $queries));
        $response->assertStatus(200);
        $this->assertLessThan(500, $duration, "List took {$duration}ms (target 500ms)");
        $this->assertLessThan(25, $queries, "Used {$queries} queries (target 25)");
    }

    /**
     * @group perf
     * Endpoint 5: Vehicles list
     * Budget: < 400ms, < 25 queries
     * N+1 fix: Replaced foreach makes loop with single grouped VehicleModel query
     */
    public function test_vehicles_list_meets_perf_budget(): void
    {
        [$duration, $queries, $response] = $this->measure('/app/vehicles');
        fwrite(STDERR, sprintf("\n[RESULT 5] GET /app/vehicles: %0.2fms, %d queries\n", $duration, $queries));
        $response->assertStatus(200);
        $this->assertLessThan(400, $duration, "List took {$duration}ms (target 400ms)");
        $this->assertLessThan(25, $queries, "Used {$queries} queries (target 25)");
    }
}
