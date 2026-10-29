<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use Database\Seeders\PerformanceBaselineSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketsReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/app/reports/tickets');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_tickets_report(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        $response = $this->actingAs($user)->get('/app/reports/tickets');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Reports/Tickets')
            ->has('filters')
            ->has('kpis')
            ->has('statusBreakdown')
            ->has('tickets')
        );
    }

    public function test_filtering_tickets_report_by_status_and_range(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $center = Center::where('tenant_id', $tenant->id)->first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();
        $customer = Customer::where('tenant_id', $tenant->id)->first();
        $vehicle = Vehicle::where('tenant_id', $tenant->id)->first();

        $wo = WorkOrder::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'code' => 'WO-TEST-999',
            'status' => 'open',
            'entry_date' => now()->toDateString(),
            'total_excl_tax' => 100,
            'total_tax' => 15,
            'total_incl_tax' => 115,
            'total_taxable_amount' => 100,
        ]);

        $response = $this->actingAs($user)->get('/app/reports/tickets?status=open&range=all');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Reports/Tickets')
            ->where('filters.status', 'open')
            ->has('tickets.data')
        );
    }

    public function test_export_tickets_report_csv(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        $response = $this->actingAs($user)->get('/app/reports/tickets/export?range=all');

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment;', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_tickets_report_group_by_returns_grouped_tickets(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $center = Center::where('tenant_id', $tenant->id)->first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();
        $customer = Customer::where('tenant_id', $tenant->id)->first();
        $vehicle = Vehicle::where('tenant_id', $tenant->id)->first();

        WorkOrder::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'code' => 'WO-GRP-1',
            'status' => 'in_progress',
            'entry_date' => now()->toDateString(),
            'total_excl_tax' => 200,
            'total_tax' => 30,
            'total_incl_tax' => 230,
            'total_taxable_amount' => 200,
        ]);

        $response = $this->actingAs($user)->get('/app/reports/tickets?group_by=status&range=all');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Reports/Tickets')
            ->where('filters.group_by', 'status')
            ->has('groupedTickets')
        );
    }

    public function test_tickets_report_with_linked_parts_and_payments(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $center = Center::where('tenant_id', $tenant->id)->first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();
        $customer = Customer::where('tenant_id', $tenant->id)->first();
        $vehicle = Vehicle::where('tenant_id', $tenant->id)->first();

        $wo = WorkOrder::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'code' => 'WO-FIN-100',
            'status' => 'done',
            'entry_date' => now()->toDateString(),
            'total_excl_tax' => 500,
            'total_tax' => 75,
            'total_incl_tax' => 575,
            'total_taxable_amount' => 500,
        ]);

        Payment::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'work_order_id' => $wo->id,
            'amount' => 300,
            'payment_date' => now(),
            'payment_method' => 'cash',
            'type' => 'payment',
        ]);

        Payment::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'work_order_id' => $wo->id,
            'amount' => 50,
            'payment_date' => now(),
            'payment_method' => 'cash',
            'type' => 'bad_debt',
        ]);

        $response = $this->actingAs($user)->get('/app/reports/tickets?range=all');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Reports/Tickets')
            ->has('kpis')
        );
    }

    public function test_orders_route_redirects_to_tickets(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        $response = $this->actingAs($user)->get('/app/reports/orders');
        $response->assertRedirect('/app/reports/tickets');
    }

    public function test_tickets_report_filters_by_specific_branch(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();
        $customer = Customer::where('tenant_id', $tenant->id)->first();
        $vehicle = Vehicle::where('tenant_id', $tenant->id)->first();

        // Create two centers for the same tenant
        $centerA = Center::create(['tenant_id' => $tenant->id, 'name' => 'Branch North', 'slug' => 'branch-north-'.uniqid()]);
        $centerB = Center::create(['tenant_id' => $tenant->id, 'name' => 'Branch South', 'slug' => 'branch-south-'.uniqid()]);

        WorkOrder::withoutGlobalScope('center_scoped')->create([
            'tenant_id' => $tenant->id,
            'center_id' => $centerA->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'code' => 'WO-NORTH-1',
            'status' => 'open',
            'entry_date' => now()->toDateString(),
            'total_excl_tax' => 100,
            'total_tax' => 15,
            'total_incl_tax' => 115,
            'total_taxable_amount' => 100,
        ]);

        WorkOrder::withoutGlobalScope('center_scoped')->create([
            'tenant_id' => $tenant->id,
            'center_id' => $centerB->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'code' => 'WO-SOUTH-1',
            'status' => 'open',
            'entry_date' => now()->toDateString(),
            'total_excl_tax' => 200,
            'total_tax' => 30,
            'total_incl_tax' => 230,
            'total_taxable_amount' => 200,
        ]);

        // Filter by Branch North
        $responseA = $this->actingAs($user)->get('/app/reports/tickets?center_id='.$centerA->id.'&range=all');
        $responseA->assertStatus(200);
        $responseA->assertInertia(fn ($page) => $page
            ->component('Reports/Tickets')
            ->where('filters.center_id', (string) $centerA->id)
            ->where('kpis.total_records', 1)
            ->has('tickets.data', 1)
            ->where('tickets.data.0.code', 'WO-NORTH-1')
        );

        // Filter by Branch South
        $responseB = $this->actingAs($user)->get('/app/reports/tickets?center_id='.$centerB->id.'&range=all');
        $responseB->assertStatus(200);
        $responseB->assertInertia(fn ($page) => $page
            ->component('Reports/Tickets')
            ->where('filters.center_id', (string) $centerB->id)
            ->where('kpis.total_records', 1)
            ->has('tickets.data', 1)
            ->where('tickets.data.0.code', 'WO-SOUTH-1')
        );

        // Filter by All Branches
        $responseAll = $this->actingAs($user)->get('/app/reports/tickets?center_id=all&range=all');
        $responseAll->assertStatus(200);
        $responseAll->assertInertia(fn ($page) => $page
            ->component('Reports/Tickets')
            ->where('filters.center_id', 'all')
            ->where('kpis.total_records', fn ($val) => $val >= 2)
        );
    }
}
