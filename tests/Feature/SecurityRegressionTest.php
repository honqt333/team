<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\InventoryBalance;
use App\Models\Part;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Security regression tests for issues fixed in the 2026-07-06 audit.
 *
 * These tests pin the protection against:
 *   1. SQL injection via `?sort=` and `?order=` query parameters on the
 *      inventory listing endpoints (InventoryBalanceController and
 *      InventoryMoveController). The fix introduced a hard whitelist of
 *      allowed columns and forced the order direction to asc/desc, so any
 *      malicious input must be ignored and the request must still complete
 *      with HTTP 200.
 *
 *   2. The v-safe-html directive (added in resources/js/Plugins/safeHtml.js)
 *      must ship in the built bundle and must call DOMPurify.sanitize. The
 *      actual DOM sanitization behaviour is covered by DOMPurify's own
 *      test-suite; here we just pin that we wired it up correctly so a
 *      future refactor cannot silently fall back to raw v-html.
 */
class SecurityRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The bundle-safety tests read every .js file in public/build/assets/
        // (~12MB at full build size) into memory. Raise the per-process limit
        // so the assertions don't OOM when the build folder is large.
        @ini_set('memory_limit', '512M');

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Permissions required by InventoryBalancePolicy / InventoryMovePolicy
        Permission::findOrCreate('inventory.stock.view', 'web');
        Permission::findOrCreate('inventory.moves.view', 'web');
    }

    protected function createUserWithPermissions(array $permissions): User
    {
        $tenant = Tenant::factory()->create();
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
        ]);
        $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);

        foreach ($permissions as $permissionName) {
            $user->givePermissionTo($permissionName);
        }

        $user->refresh();

        return $user;
    }

    // ─────────────────────────────────────────────────────────────────
    // SQL injection regression — InventoryBalanceController
    // ─────────────────────────────────────────────────────────────────

    public function test_inventory_stock_index_rejects_sql_injection_in_sort(): void
    {
        $user = $this->createUserWithPermissions(['inventory.stock.view']);

        $tenantId = $user->tenant_id;
        $centerId = $user->current_center_id;

        $warehouse = Warehouse::factory()->create([
            'center_id' => $centerId,
            'is_default' => true,
        ]);

        $part = Part::factory()->create(['tenant_id' => $tenantId]);
        InventoryBalance::create([
            'tenant_id' => $tenantId,
            'center_id' => $centerId,
            'warehouse_id' => $warehouse->id,
            'part_id' => $part->id,
            'qty_on_hand' => 10,
            'min_stock' => 1,
            'wac_cost' => 5,
        ]);

        // A malicious `sort` value that would try to break out of the ORDER BY
        // clause if it were passed straight to SQL. After the fix, the value
        // must be ignored and the request must still respond 200.
        $maliciousSort = '(SELECT CASE WHEN (SELECT COUNT(*) FROM users) > 0 THEN 1 ELSE qty_on_hand END)';

        $response = $this->actingAs($user)
            ->get('/app/inventory/stock?sort='.urlencode($maliciousSort));

        $response->assertStatus(200);
    }

    public function test_inventory_stock_index_rejects_sql_injection_in_order(): void
    {
        $user = $this->createUserWithPermissions(['inventory.stock.view']);

        // The fix forces `order` to either `asc` or `desc`; any other value
        // (e.g. UNION attack) must be coerced to `desc`. Request still 200s.
        $response = $this->actingAs($user)
            ->get('/app/inventory/stock?order='.urlencode('ASC; DROP TABLE users'));

        $response->assertStatus(200);
    }

    public function test_inventory_stock_index_accepts_whitelisted_sort_values(): void
    {
        $user = $this->createUserWithPermissions(['inventory.stock.view']);

        foreach (['qty_on_hand', 'min_stock', 'created_at', 'sku', 'name'] as $sort) {
            $response = $this->actingAs($user)
                ->get('/app/inventory/stock?sort='.$sort.'&order=asc');

            $response->assertStatus(200);
        }
    }

    public function test_inventory_stock_index_rejects_unknown_sort_value(): void
    {
        $user = $this->createUserWithPermissions(['inventory.stock.view']);

        // An unknown column name must be ignored — request still 200s and
        // falls back to the default sort. We do not assert which column is
        // used (DB engine may differ), only that no exception is thrown.
        $response = $this->actingAs($user)
            ->get('/app/inventory/stock?sort=password');

        $response->assertStatus(200);
    }

    // ─────────────────────────────────────────────────────────────────
    // SQL injection regression — InventoryMoveController
    // ─────────────────────────────────────────────────────────────────

    public function test_inventory_moves_index_rejects_sql_injection_in_sort(): void
    {
        $user = $this->createUserWithPermissions(['inventory.moves.view']);

        $maliciousSort = 'posted_at; UPDATE users SET is_admin = 1 WHERE id = '.$user->id;

        $response = $this->actingAs($user)
            ->get('/app/inventory/moves?sort='.urlencode($maliciousSort));

        $response->assertStatus(200);

        // The user must NOT have been promoted as a side effect.
        $user->refresh();
        $this->assertFalse(
            (bool) ($user->is_admin ?? false),
            'SQL injection should not have promoted the attacker to admin',
        );
    }

    public function test_inventory_moves_index_accepts_whitelisted_sort_values(): void
    {
        $user = $this->createUserWithPermissions(['inventory.moves.view']);

        foreach (['posted_at', 'part_id', 'qty', 'created_at', 'sku'] as $sort) {
            $response = $this->actingAs($user)
                ->get('/app/inventory/moves?sort='.$sort.'&order=desc');

            $response->assertStatus(200);
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // v-safe-html directive — built bundle integration check
    // ─────────────────────────────────────────────────────────────────

    public function test_safe_html_directive_is_registered_in_app_plugin(): void
    {
        $source = file_get_contents(base_path('resources/js/app.js'));
        $plugin = file_get_contents(base_path('resources/js/Plugins/safeHtml.js'));

        $this->assertStringContainsString(
            "from './Plugins/safeHtml'",
            $source,
            'app.js must import the SafeHtmlPlugin',
        );

        $this->assertStringContainsString(
            '.use(SafeHtmlPlugin)',
            $source,
            'app.js must register the SafeHtmlPlugin on the Vue app',
        );

        $this->assertStringContainsString(
            "app.directive('safe-html'",
            $plugin,
            'safeHtml.js must register a Vue directive called safe-html',
        );

        $this->assertStringContainsString(
            'DOMPurify.sanitize',
            $plugin,
            'safeHtml.js must call DOMPurify.sanitize when binding the directive',
        );
    }

    public function test_safe_html_directive_is_bundled_into_built_assets(): void
    {
        $buildDir = public_path('build/assets');
        $this->assertDirectoryExists($buildDir, 'Vite build output is missing');

        $files = glob($buildDir.'/*.js');
        $this->assertNotEmpty($files, 'No built JS bundles found');

        $bundleText = '';

        foreach ($files as $file) {
            $bundleText .= file_get_contents($file);
        }

        // After Vite minification, "v-safe-html" may not appear verbatim, but
        // the directive name and DOMPurify should both be present in the
        // shared vendor bundle.
        $this->assertStringContainsString(
            'safe-html',
            $bundleText,
            'safe-html directive name should be present in the built bundle',
        );
        $this->assertStringContainsString(
            'dompurify',
            strtolower($bundleText),
            'dompurify should be bundled (case-insensitive check)',
        );
    }
}
