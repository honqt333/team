<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DashboardTimezoneBoundaryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function createTenantContext(string $timezone, string $country = 'SA'): array
    {
        $tenant = Tenant::factory()->create([
            'status' => 'active',
            'country' => $country,
            'timezone' => $timezone,
        ]);

        $center = Center::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => true,
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        setPermissionsTeamId($tenant->id);

        $role = Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'web',
            'tenant_id' => $tenant->id,
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
            'is_system_admin' => true,
        ]);
        $user->assignRole($role);
        $user->centers()->attach([$center->id => ['tenant_id' => $tenant->id]]);

        $customer = Customer::factory()->create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
        ]);

        $vehicle = Vehicle::factory()->create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
        ]);

        return compact('tenant', 'center', 'user', 'customer', 'vehicle');
    }

    private function createWorkOrderAt(array $ctx, Carbon $utcAt, float $total = 500.00, ?string $expectedEndDate = null, string $status = 'done'): WorkOrder
    {
        $wo = new WorkOrder;
        $wo->forceFill([
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'customer_id' => $ctx['customer']->id,
            'vehicle_id' => $ctx['vehicle']->id,
            'code' => 'WO-'.random_int(10000, 99999),
            'status' => $status,
            'expected_end_date' => $expectedEndDate,
            'total_incl_tax' => $total,
            'created_at' => $utcAt,
            'closed_at' => $status === 'done' ? $utcAt : null,
        ]);
        $wo->saveQuietly();

        return $wo;
    }

    private function createInvoiceAt(array $ctx, Carbon $utcAt, float $total = 500.00, string $type = 'invoice', ?WorkOrder $wo = null): Invoice
    {
        return Invoice::factory()->create([
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'customer_id' => $ctx['customer']->id,
            'work_order_id' => $wo?->id,
            'type' => $type,
            'status' => 'valid',
            'issue_date' => $utcAt,
            'supply_date' => $utcAt->copy()->timezone($ctx['tenant']->timezone)->toDateString(),
            'total_incl_tax' => $total,
        ]);
    }

    private function createPaymentAt(array $ctx, Invoice $inv, Carbon $utcAt, float $amount): void
    {
        Payment::forceCreate([
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'work_order_id' => $inv->work_order_id,
            'invoice_id' => $inv->id,
            'amount' => $amount,
            'type' => Payment::TYPE_PAYMENT,
            'payment_method' => 'cash',
            'payment_date' => $utcAt,
            'received_by' => $ctx['user']->id,
        ]);
    }

    public function test_s1_s2_s3_riyadh_midnight_month_boundary_and_closing(): void
    {
        $ctx = $this->createTenantContext('Asia/Riyadh');

        // 2026-11-30 23:59:59 Asia/Riyadh == 2026-11-30 20:59:59 UTC (November)
        $novLastSecond = Carbon::parse('2026-11-30 23:59:59', 'Asia/Riyadh')->utc();
        // 2026-12-01 00:00:00 Asia/Riyadh == 2026-11-30 21:00:00 UTC (December!)
        $decFirstSecond = Carbon::parse('2026-12-01 00:00:00', 'Asia/Riyadh')->utc();
        // 2026-12-01 01:00:00 Asia/Riyadh == 2026-11-30 22:00:00 UTC (December!)
        $decOneAm = Carbon::parse('2026-12-01 01:00:00', 'Asia/Riyadh')->utc();

        $woNov = $this->createWorkOrderAt($ctx, $novLastSecond, 200.00);
        $invNov = $this->createInvoiceAt($ctx, $novLastSecond, 200.00, 'invoice', $woNov);
        $this->createPaymentAt($ctx, $invNov, $novLastSecond, 200.00);

        $woDec1 = $this->createWorkOrderAt($ctx, $decFirstSecond, 300.00);
        $invDec1 = $this->createInvoiceAt($ctx, $decFirstSecond, 300.00, 'invoice', $woDec1);
        $this->createPaymentAt($ctx, $invDec1, $decFirstSecond, 300.00);

        $woDec2 = $this->createWorkOrderAt($ctx, $decOneAm, 500.00);
        $invDec2 = $this->createInvoiceAt($ctx, $decOneAm, 500.00, 'invoice', $woDec2);
        $this->createPaymentAt($ctx, $invDec2, $decOneAm, 500.00);

        // S1 & S2 at 2026-12-01 10:00:00 Asia/Riyadh (2026-12-01 07:00:00 UTC)
        Carbon::setTestNow(Carbon::parse('2026-12-01 07:00:00', 'UTC'));

        $resp = $this->actingAs($ctx['user'])->get('/dashboard?period=month');
        $resp->assertOk();
        $props = $resp->viewData('page')['props'];

        $this->assertEquals(800.00, $props['stats']['revenue']['thisMonth']);
        $this->assertEquals(200.00, $props['stats']['revenue']['lastMonth']);
        $this->assertEquals(800.00, $props['stats']['revenue']['paymentsToday']);
        $this->assertEquals(2, $props['stats']['workOrders']['thisMonth']);
        $this->assertEquals(1, $props['stats']['workOrders']['lastMonth']);
        $this->assertEquals(2, $props['stats']['workOrders']['completedToday']);

        // S3: period=today compares Dec 1 (800) vs Nov 30 (200) with zero loss or double counting
        $respToday = $this->actingAs($ctx['user'])->get('/dashboard?period=today');
        $respToday->assertOk();
        $todayProps = $respToday->viewData('page')['props'];

        $this->assertEquals(800.00, $todayProps['stats']['revenue']['periodTotal']);
        $this->assertEquals(200.00, $todayProps['stats']['revenue']['prevPeriodTotal']);
        $this->assertEquals(2, $todayProps['stats']['workOrders']['periodCount']);
        $this->assertEquals(1, $todayProps['stats']['workOrders']['prevPeriodCount']);
    }

    public function test_s4_s5_s6_charts_group_by_local_calendar_days_and_months(): void
    {
        $ctx = $this->createTenantContext('Asia/Riyadh');

        // 2027-01-01 01:30:00 Asia/Riyadh == 2026-12-31 22:30:00 UTC (New Year boundary!)
        $jan1Riyadh = Carbon::parse('2027-01-01 01:30:00', 'Asia/Riyadh')->utc();
        $woJan1 = $this->createWorkOrderAt($ctx, $jan1Riyadh, 450.00);
        $this->createInvoiceAt($ctx, $jan1Riyadh, 450.00, 'invoice', $woJan1);

        // 2026-12-31 23:30:00 Asia/Riyadh == 2026-12-31 20:30:00 UTC
        $dec31Riyadh = Carbon::parse('2026-12-31 23:30:00', 'Asia/Riyadh')->utc();
        $woDec31 = $this->createWorkOrderAt($ctx, $dec31Riyadh, 150.00);
        $this->createInvoiceAt($ctx, $dec31Riyadh, 150.00, 'invoice', $woDec31);

        // Now = 2027-01-01 02:00:00 Asia/Riyadh (2026-12-31 23:00:00 UTC)
        Carbon::setTestNow(Carbon::parse('2026-12-31 23:00:00', 'UTC'));

        $resp = $this->actingAs($ctx['user'])->get('/dashboard?period=month');
        $resp->assertOk();
        $charts = $resp->viewData('page')['props']['charts'];

        // S4: exactly 7 local calendar days ending 2027-01-01
        $this->assertCount(7, $charts['weeklyWorkOrders']);
        $this->assertSame('2027-01-01', end($charts['weeklyWorkOrders'])['date']);
        $this->assertSame(1, end($charts['weeklyWorkOrders'])['count']);

        // S5: exactly 30 local calendar days ending 2027-01-01
        $this->assertCount(30, $charts['revenueDaily']);
        $this->assertSame('2027-01-01', end($charts['revenueDaily'])['date']);
        $this->assertEquals(450.00, end($charts['revenueDaily'])['total']);

        $dec31Bar = collect($charts['revenueDaily'])->firstWhere('date', '2026-12-31');
        $this->assertNotNull($dec31Bar);
        $this->assertEquals(150.00, $dec31Bar['total']);

        // S6: revenueMonthly puts 450 in 2027-01 and 150 in 2026-12
        $janBar = collect($charts['revenueMonthly'])->firstWhere('month', '2027-01');
        $decBar = collect($charts['revenueMonthly'])->firstWhere('month', '2026-12');
        $this->assertNotNull($janBar);
        $this->assertNotNull($decBar);
        $this->assertEquals(450.00, $janBar['total']);
        $this->assertEquals(150.00, $decBar['total']);
    }

    public function test_s7_s8_s9_other_timezones_dst_previous_periods_and_tenant_isolation(): void
    {
        // Dubai (UTC+4) and Cairo (DST UTC+3 in July 2026)
        $dubaiCtx = $this->createTenantContext('Asia/Dubai', 'AE');
        $cairoCtx = $this->createTenantContext('Africa/Cairo', 'EG');

        // 2026-07-01 00:30:00 Asia/Dubai == 2026-06-30 20:30:00 UTC (July in Dubai, June in Cairo UTC+3 -> 23:30 June 30!)
        $instantUtc = Carbon::parse('2026-06-30 20:30:00', 'UTC');

        $woDubai = $this->createWorkOrderAt($dubaiCtx, $instantUtc, 700.00);
        $this->createInvoiceAt($dubaiCtx, $instantUtc, 700.00, 'invoice', $woDubai);

        $woCairo = $this->createWorkOrderAt($cairoCtx, $instantUtc, 900.00);
        $this->createInvoiceAt($cairoCtx, $instantUtc, 900.00, 'invoice', $woCairo);

        // Evaluate at 2026-07-01 10:00:00 UTC (July 1 in both cities)
        Carbon::setTestNow(Carbon::parse('2026-07-01 10:00:00', 'UTC'));

        $dubaiResp = $this->actingAs($dubaiCtx['user'])->get('/dashboard?period=7d');
        $dubaiResp->assertOk();
        $dubaiRev = $dubaiResp->viewData('page')['props']['stats']['revenue'];
        $this->assertEquals(700.00, $dubaiRev['thisMonth']);
        $this->assertEquals(0.00, $dubaiRev['lastMonth']);
        $this->assertEquals(700.00, $dubaiRev['periodTotal']);
        $this->assertEquals(0.00, $dubaiRev['prevPeriodTotal']);

        $cairoResp = $this->actingAs($cairoCtx['user'])->get('/dashboard?period=7d');
        $cairoResp->assertOk();
        $cairoRev = $cairoResp->viewData('page')['props']['stats']['revenue'];
        $this->assertEquals(0.00, $cairoRev['thisMonth']);
        $this->assertEquals(900.00, $cairoRev['lastMonth']);
        $this->assertEquals(900.00, $cairoRev['periodTotal']);
        $this->assertEquals(0.00, $cairoRev['prevPeriodTotal']);
    }

    public function test_s10_s11_date_only_columns_and_cache_boundary(): void
    {
        $ctx = $this->createTenantContext('Asia/Riyadh');

        // At 2026-12-01 01:00:00 Asia/Riyadh (2026-11-30 22:00:00 UTC)
        Carbon::setTestNow(Carbon::parse('2026-11-30 22:00:00', 'UTC'));

        $supplier = Supplier::create([
            'tenant_id' => $ctx['tenant']->id,
            'name' => 'TZ Supplier',
        ]);

        // Date-only PurchaseInvoice for local Dec 1 and WorkOrder with expected_end_date = 2026-12-01
        PurchaseInvoice::create([
            'tenant_id' => $ctx['tenant']->id,
            'center_id' => $ctx['center']->id,
            'supplier_id' => $supplier->id,
            'code' => 'PI-TZ-01',
            'invoice_number' => 'PI-TZ-01',
            'issue_date' => '2026-12-01',
            'status' => 'posted',
            'subtotal' => 100.00,
            'tax_amount' => 15.00,
            'total' => 115.00,
        ]);

        $this->createWorkOrderAt($ctx, Carbon::now(), 300.00, '2026-12-01', 'open');

        $resp = $this->actingAs($ctx['user'])->get('/dashboard?period=month');
        $resp->assertOk();
        $props = $resp->viewData('page')['props'];

        $this->assertEquals(115.00, $props['stats']['revenue']['periodExpenses']);
        $this->assertCount(1, $props['todaySchedule']);
        $this->assertSame(0, $props['alerts']['overdueWorkOrders']);

        // S11: Cache key must change across local midnight (2026-12-01 23:59:50 -> 2026-12-02 00:00:10 Riyadh)
        Carbon::setTestNow(Carbon::parse('2026-12-01 23:59:50', 'Asia/Riyadh')->utc());
        $beforeMidnight = $this->actingAs($ctx['user'])->get('/dashboard?period=today');
        $beforeMidnight->assertOk();
        $this->assertSame('2026-12-01', end($beforeMidnight->viewData('page')['props']['charts']['revenueDaily'])['date']);

        Carbon::setTestNow(Carbon::parse('2026-12-02 00:00:10', 'Asia/Riyadh')->utc());
        $afterMidnight = $this->actingAs($ctx['user'])->get('/dashboard?period=today');
        $afterMidnight->assertOk();
        $this->assertSame('2026-12-02', end($afterMidnight->viewData('page')['props']['charts']['revenueDaily'])['date']);
    }
}
