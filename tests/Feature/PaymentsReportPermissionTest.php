<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Tests for the permission required to read the payments report
 * (audit finding #51).
 *
 * The report of the payments and its CSV export live under the app
 * guard, which only requires a signed-in user of the tenant. The
 * report hands out the amounts that were collected, the customers and
 * the invoices behind them — the same records that reading a payment
 * requires PAYMENTS_VIEW for.
 *
 * Without a gate any user of the app could open the report and
 * download the export, whatever their role was.
 */
class PaymentsReportPermissionTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Center $center;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate(Permissions::PAYMENTS_VIEW, 'web');
        Permission::findOrCreate(Permissions::PAYMENTS_CREATE, 'web');

        $this->tenant = Tenant::factory()->create();
        $this->center = Center::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    private function userWith(array $permissions): User
    {
        $user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
        ]);

        $user->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
        $user->givePermissionTo($permissions);
        $user->refresh();

        return $user;
    }

    public function test_a_user_without_the_payments_permission_cannot_open_the_report(): void
    {
        $user = $this->userWith([Permissions::PAYMENTS_CREATE]);

        $response = $this->actingAs($user)->get('/app/reports/payments');

        $response->assertForbidden();
    }

    public function test_a_user_without_the_payments_permission_cannot_export_the_report(): void
    {
        $user = $this->userWith([Permissions::PAYMENTS_CREATE]);

        $response = $this->actingAs($user)->get('/app/reports/payments/export');

        $response->assertForbidden();
    }

    public function test_a_user_with_the_payments_permission_can_open_the_report(): void
    {
        $user = $this->userWith([Permissions::PAYMENTS_VIEW]);

        Payment::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'amount' => 115,
            'payment_date' => now(),
            'payment_method' => 'mada',
            'type' => 'payment',
            'reference' => 'PERM-1',
        ]);

        $response = $this->actingAs($user)->get('/app/reports/payments');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Reports/Payments')
            ->has('kpis')
            ->has('payments')
        );
    }

    public function test_a_user_with_the_payments_permission_can_export_the_report(): void
    {
        $user = $this->userWith([Permissions::PAYMENTS_VIEW]);

        Payment::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'amount' => 115,
            'payment_date' => now(),
            'payment_method' => 'mada',
            'type' => 'payment',
            'reference' => 'PERM-2',
        ]);

        $response = $this->actingAs($user)->get('/app/reports/payments/export');

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_a_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/app/reports/payments');

        $response->assertRedirect('/login');
    }
}
