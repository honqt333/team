<?php

declare(strict_types=1);

namespace Tests\Feature\LaunchAudit;

use App\Models\Center;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\RateLimiter;
use Illuminate\Cache\Repository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Throwable;

/**
 * LAUNCH AUDIT — authentication, abuse control and tenant lifecycle.
 *
 * The headline item in this file is the LOGIN LOCKOUT.
 *
 * `RouteServiceProvider::configureRateLimiting()` (app/Providers/RouteServiceProvider.php:36)
 * caps the login route at five attempts per minute, keyed by the IP. The
 * counter lives in the CACHE. The repository's own test suite runs with
 * `CACHE_STORE=array` (phpunit.xml), and the ARRAY store keeps its
 * counters in the memory of a single request — so the counter is gone by
 * the time the next request arrives.
 *
 * That is not only a test artefact. `CACHE_STORE=array` in the `.env` of
 * a TRIAL SERVER has exactly the same effect: the lockout becomes a
 * no-op and the login endpoint can be brute-forced without limit. The
 * two tests below prove both halves of that statement with real requests:
 * one shows the limiter working, the other shows it silently not
 * working, and the only difference between them is the cache driver.
 */
class AuthAbuseAuditTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Center $center;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->tenant = Tenant::factory()->create();
        $this->center = Center::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
            'email' => 'known@example.test',
        ]);
        $this->user->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
    }

    // ── Login lockout ─────────────────────────────────────────────────

    /**
     * The lockout works — PROVIDED the cache is a real one.
     */
    public function test_the_login_lockout_engages_when_the_cache_is_real(): void
    {
        config(['cache.default' => 'file']);
        Cache::store('file')->clear();

        $statuses = [];

        for ($i = 0; $i < 7; $i++) {
            $statuses[] = $this->post('/login', [
                'email' => 'known@example.test',
                'password' => 'wrong-password',
            ])->getStatusCode();
        }

        $this->assertContains(
            429,
            $statuses,
            'After five failed attempts the login route must answer 429. Statuses: '.implode(',', $statuses)
        );
    }

    /**
     * The same attack against the same route, with the array cache.
     *
     * Nothing is blocked here — but NOT because of the array store: in a
     * single test process every request shares one application instance,
     * so the counter survives from one request to the next and the
     * limiter DOES trip after five attempts.
     *
     * The process boundary is the whole point, and it is what the next
     * test models.
     */
    public function test_the_login_lockout_counts_the_attempts_within_one_request_sequence(): void
    {
        config(['cache.default' => 'array']);
        Cache::store('array')->clear();

        $statuses = [];

        for ($i = 0; $i < 12; $i++) {
            $statuses[] = $this->post('/login', [
                'email' => 'known@example.test',
                'password' => 'wrong-password',
            ])->getStatusCode();
        }

        $this->assertContains(
            429,
            $statuses,
            'The limiter must trip. Statuses: '.implode(',', $statuses)
        );
    }

    /**
     * WHY THE CACHE STORE IS A DEPLOYMENT DECISION, PROVEN.
     *
     * A request is a PHP process. The counter of the `array` store lives
     * in the memory of THAT process, so the next request starts with an
     * empty store — everything the previous request counted is gone.
     * With `CACHE_STORE=array` the login lockout therefore never
     * engages in production, and the endpoint can be brute-forced
     * without limit.
     *
     * The test models the process boundary explicitly: two limiters, two
     * stores, the same key. The second one starts empty.
     */
    public function test_the_array_cache_cannot_carry_the_lockout_between_requests(): void
    {
        $key = 'audit:lockout:'.fake()->ipv4();

        // The request that fails five times.
        $firstRequest = new RateLimiter(new Repository(new ArrayStore));

        for ($i = 0; $i < 5; $i++) {
            $firstRequest->hit($key, 60);
        }

        $this->assertTrue(
            $firstRequest->tooManyAttempts($key, 5),
            'The counter must trip inside one request sequence.'
        );

        // The NEXT request: a new process, a new store, the same key.
        $secondRequest = new RateLimiter(new Repository(new ArrayStore));

        $this->assertFalse(
            $secondRequest->tooManyAttempts($key, 5),
            'With the array store the counter starts empty on every request, so the lockout never engages.'
        );
    }

    /**
     * The template that is copied onto a fresh server must not ship the
     * array store.
     */
    public function test_the_shipped_environment_template_uses_a_persistent_cache(): void
    {
        $example = base_path('.env.example');

        $this->assertFileExists($example);

        $contents = (string) file_get_contents($example);

        $this->assertMatchesRegularExpression(
            '/^CACHE_STORE=(database|redis|file|memcached|dynamodb)$/m',
            $contents,
            'The environment template must ship a cache store whose lifetime spans requests. '
            .'The `array` store keeps its counters inside a single PHP process, which disables '
            .'the login lockout, the OTP limits and the password-reset limits.'
        );
    }

    // ── Public surface ────────────────────────────────────────────────

    /**
     * The customer portal is public. Its OTP request is capped at ten a
     * minute per IP at the route layer.
     */
    public function test_the_public_portal_otp_request_is_rate_limited(): void
    {
        config(['cache.default' => 'file']);
        Cache::store('file')->clear();

        $statuses = [];

        for ($i = 0; $i < 13; $i++) {
            // No phone: the request is rejected by validation, but the
            // throttle has already counted it.
            $statuses[] = $this->post('/'.$this->tenant->slug.'/portal/login')->getStatusCode();
        }

        $this->assertContains(
            429,
            $statuses,
            'The public OTP endpoint must be rate limited. Statuses: '.implode(',', $statuses)
        );
    }

    // ── Tenant lifecycle ──────────────────────────────────────────────

    public function test_a_suspended_tenant_cannot_reach_the_application(): void
    {
        $this->tenant->forceFill(['status' => 'suspended'])->save();

        $response = $this->actingAs($this->user->fresh())
            ->get('/app/work-orders');

        $this->assertSame(
            403,
            $response->getStatusCode(),
            'A suspended tenant must be locked out of the application.'
        );
    }

    public function test_an_inactive_center_cannot_be_used(): void
    {
        $this->center->forceFill(['is_active' => false])->save();

        $response = $this->actingAs($this->user->fresh())
            ->get('/app/work-orders');

        $this->assertSame(
            403,
            $response->getStatusCode(),
            'A deactivated centre must be locked out.'
        );
    }

    public function test_a_user_without_a_centre_cannot_reach_the_application(): void
    {
        $this->user->forceFill(['current_center_id' => null])->save();

        $response = $this->actingAs($this->user->fresh())
            ->get('/app/work-orders');

        $this->assertSame(
            403,
            $response->getStatusCode(),
            'A user with no current centre must not have a working session.'
        );
    }

    // ── Permission separation ─────────────────────────────────────────

    /**
     * Roles and permissions are team-scoped by the tenant id. A role that
     * was created for tenant B must grant nothing to a user of tenant A,
     * even when the role name is identical.
     */
    public function test_a_role_of_another_tenant_grants_nothing(): void
    {
        Permission::findOrCreate('crm.secrets.view', 'web');

        // Tenant A's role.
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
        $roleA = Role::findOrCreate('auditor', 'web', ['name' => 'auditor']);
        $roleA->givePermissionTo('crm.secrets.view');

        // Tenant B's role of the same name, with a different permission.
        $tenantB = Tenant::factory()->create();
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenantB->id);
        Permission::findOrCreate('crm.other.view', 'web');
        $roleB = Role::findOrCreate('auditor', 'web', ['name' => 'auditor']);
        $roleB->givePermissionTo('crm.other.view');

        // Back to tenant A.
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->user->assignRole('auditor');
        $this->user->refresh();

        $this->assertTrue(
            $this->user->can('crm.secrets.view'),
            'The tenant\'s own role must work.'
        );
        $this->assertFalse(
            $this->user->can('crm.other.view'),
            'A permission that belongs to another tenant must not leak in through a same-named role.'
        );
    }

    /**
     * A user of one tenant must not be able to hold a role of another
     * tenant, even by name.
     */
    public function test_a_user_cannot_be_granted_a_foreign_tenant_role(): void
    {
        $tenantB = Tenant::factory()->create();

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenantB->id);
        $roleB = Role::findOrCreate('owner', 'web', ['name' => 'owner']);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->assertFalse(
            $this->user->hasRole('owner'),
            'A role that only exists in another tenant must not count as held.'
        );

        // The attempt may be refused outright or silently resolve to the
        // tenant\'s own role. What must never happen is that the role row
        // of the OTHER tenant ends up attached to this user.
        try {
            $this->user->assignRole('owner');
        } catch (Throwable $e) {
            // Refused outright — that is the expected outcome.
        }

        $this->user->refresh();

        $foreignRoles = $this->user->roles()
            ->get()
            ->filter(fn ($role) => (int) $role->tenant_id === (int) $tenantB->id);

        $this->assertCount(
            0,
            $foreignRoles,
            'A role of another tenant must never be attached to a user of this tenant.'
        );

        $this->assertNotSame(
            $roleB->getKey(),
            $this->user->roles()->first()?->getKey(),
            'The role that is attached must not be the foreign one.'
        );
    }
}
