<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PerformanceBaselineSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsHubTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/app/reports');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_reports_hub_with_kpis(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        $response = $this->actingAs($user)->get('/app/reports?range=this_month');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Reports/Hub')
            ->has('filters')
            ->has('kpis')
            ->has('paymentsByMethod')
            ->has('recentPayments')
            ->has('recentInvoices')
            ->has('recentWorkOrders')
            ->has('topServices')
            ->has('technicians')
        );
    }

    public function test_reports_can_filter_by_different_ranges(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        $response = $this->actingAs($user)->get('/app/reports?range=today');
        $response->assertStatus(200);

        $responseAll = $this->actingAs($user)->get('/app/reports?range=all');
        $responseAll->assertStatus(200);
    }
}
