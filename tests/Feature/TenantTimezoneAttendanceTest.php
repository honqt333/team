<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Permissions;
use App\Support\TenantClock;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TenantTimezoneAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_asia_riyadh_today_differs_from_utc_late_evening(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-14 23:30:00', 'UTC'));

        $tenant = Tenant::factory()->create([
            'status' => 'active',
            'timezone' => 'Asia/Riyadh',
        ]);
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
            'two_factor_confirmed_at' => now(),
        ]);
        $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        Permission::findOrCreate(Permissions::HR_ATTENDANCE_VIEW, 'web');
        $user->givePermissionTo(Permissions::HR_ATTENDANCE_VIEW);

        $this->assertSame('2026-11-15', TenantClock::todayDateString($tenant));

        $this->actingAs($user)
            ->get('/app/hr/attendance')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.date', '2026-11-15')
            );
    }

    public function test_other_timezones_and_utc_day_differences_are_handled_correctly(): void
    {
        // 02:30 UTC on 2026-11-15 is still 2026-11-14 21:30 in America/New_York (EST -5)
        Carbon::setTestNow(Carbon::parse('2026-11-15 02:30:00', 'UTC'));

        $nyTenant = Tenant::factory()->create(['timezone' => 'America/New_York']);
        $tokyoTenant = Tenant::factory()->create(['timezone' => 'Asia/Tokyo']);

        $this->assertSame('2026-11-14', TenantClock::todayDateString($nyTenant));
        $this->assertSame('2026-11-15', TenantClock::todayDateString($tokyoTenant));
    }

    public function test_midnight_crossing_updates_local_date(): void
    {
        $tenant = Tenant::factory()->create(['timezone' => 'Asia/Riyadh']);

        Carbon::setTestNow(Carbon::parse('2026-11-14 20:59:59', 'UTC')); // 23:59:59 Riyadh
        $this->assertSame('2026-11-14', TenantClock::todayDateString($tenant));

        Carbon::setTestNow(Carbon::parse('2026-11-14 21:00:01', 'UTC')); // 00:00:01 Riyadh
        $this->assertSame('2026-11-15', TenantClock::todayDateString($tenant));
    }

    public function test_dst_timezone_shifts_local_day_boundary(): void
    {
        $nyTenant = Tenant::factory()->create(['timezone' => 'America/New_York']);

        // Summer EDT (UTC-4): 03:30 UTC is 23:30 previous day
        Carbon::setTestNow(Carbon::parse('2026-07-10 03:30:00', 'UTC'));
        $this->assertSame('2026-07-09', TenantClock::todayDateString($nyTenant));

        // 04:30 UTC is 00:30 current day in EDT
        Carbon::setTestNow(Carbon::parse('2026-07-10 04:30:00', 'UTC'));
        $this->assertSame('2026-07-10', TenantClock::todayDateString($nyTenant));
    }

    public function test_repeated_reads_at_frozen_time_are_idempotent_and_invalid_tz_falls_back_to_utc(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-14 23:30:00', 'UTC'));

        $riyadhTenant = Tenant::factory()->create(['timezone' => 'Asia/Riyadh']);
        $first = TenantClock::todayDateString($riyadhTenant);
        $second = TenantClock::todayDateString($riyadhTenant);
        $this->assertSame($first, $second);
        $this->assertSame('2026-11-15', $first);

        $invalidTenant = Tenant::factory()->create(['timezone' => 'Invalid/Timezone']);
        $this->assertSame('UTC', TenantClock::resolveTimezone($invalidTenant));
        $this->assertSame('2026-11-14', TenantClock::todayDateString($invalidTenant));
    }
}
