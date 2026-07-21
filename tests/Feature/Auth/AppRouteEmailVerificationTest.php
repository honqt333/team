<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\Center;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Regression tests for email verification enforcement on the
 * /app/* routes. Pin the bug fixed in 2026-07-06 audit: the second
 * Route::prefix('app')->middleware(...) group (line 86 of web.php
 * at the time of the bug) had `auth, tenant.active, center.context,
 * EnsureTwoFactorEnabled` but NO `verified`. That meant any
 * authenticated user — even one with email_verified_at = NULL —
 * could browse customers, work orders, inventory, etc. The
 * `verified` middleware was effectively only on /dashboard and
 * the small /app/profile, /app/security, /app/my group above.
 */
class AppRouteEmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function createUnverifiedUser(): User
    {
        $tenant = Tenant::factory()->create();
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);

        return User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
            'email_verified_at' => null,
        ]);
    }

    public function test_unverified_user_cannot_view_customers_index(): void
    {
        $user = $this->createUnverifiedUser();

        $response = $this->actingAs($user)->get('/app/customers');
        $response->assertRedirect(route('verification.notice'));
    }

    public function test_unverified_user_cannot_view_inventory_stock(): void
    {
        $user = $this->createUnverifiedUser();

        $response = $this->actingAs($user)->get('/app/inventory/stock');
        $response->assertRedirect(route('verification.notice'));
    }

    public function test_unverified_user_cannot_view_work_orders_index(): void
    {
        $user = $this->createUnverifiedUser();

        $response = $this->actingAs($user)->get('/app/work-orders');
        $response->assertRedirect(route('verification.notice'));
    }

    public function test_unverified_user_cannot_view_hr_employees(): void
    {
        $user = $this->createUnverifiedUser();

        $response = $this->actingAs($user)->get('/app/hr/employees');
        $response->assertRedirect(route('verification.notice'));
    }

    public function test_unverified_user_cannot_view_reports(): void
    {
        $user = $this->createUnverifiedUser();

        $response = $this->actingAs($user)->get('/app/reports');
        $response->assertRedirect(route('verification.notice'));
    }

    public function test_unverified_user_cannot_access_purchasing_invoices(): void
    {
        $user = $this->createUnverifiedUser();

        $response = $this->actingAs($user)->get('/app/invoices/purchases');
        $response->assertRedirect(route('verification.notice'));
    }

    public function test_unverified_user_cannot_access_employee_portal(): void
    {
        $user = $this->createUnverifiedUser();

        // Make sure the employee role exists in this tenant so the
        // Spatie middleware can resolve it. The role is normally
        // seeded by TenantSetupService when a real tenant is created.
        $role = Role::firstOrCreate([
            'tenant_id' => $user->tenant_id,
            'name' => 'employee',
            'guard_name' => 'web',
        ]);
        app(PermissionRegistrar::class)->setPermissionsTeamId($user->tenant_id);
        $user->assignRole($role);

        $response = $this->actingAs($user)->get('/app/my');
        $response->assertRedirect(route('verification.notice'));
    }
}
