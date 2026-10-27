<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleMake;
use App\Models\VehicleModel;
use App\Models\WorkOrder;
use Database\Seeders\PerformanceBaselineSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentsReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/app/reports/payments');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_payments_report(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        $response = $this->actingAs($user)->get('/app/reports/payments');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Reports/Payments')
            ->has('filters')
            ->has('kpis')
            ->has('methodsBreakdown')
            ->has('payments')
        );
    }

    public function test_filtering_payments_report_by_method_and_range(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $center = Center::where('tenant_id', $tenant->id)->first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();
        $invoice = Invoice::where('tenant_id', $tenant->id)->first();

        Payment::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'invoice_id' => $invoice?->id,
            'amount' => 115,
            'payment_date' => now(),
            'payment_method' => 'mada',
            'type' => 'payment',
            'reference' => 'REC-MADA-100',
        ]);

        $response = $this->actingAs($user)->get('/app/reports/payments?method=mada&range=all');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Reports/Payments')
            ->where('filters.method', 'mada')
            ->has('payments.data')
        );
    }

    public function test_export_payments_report_csv(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        $response = $this->actingAs($user)->get('/app/reports/payments/export?range=all');

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment;', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_payments_report_handles_null_or_empty_filter_parameters(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        // Pass null / empty query parameters to ensure no TypeErrors occur
        $response = $this->actingAs($user)->get('/app/reports/payments?method=&type=&customer_id=&search=&range=custom&start_date=&end_date=');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Reports/Payments')
            ->where('filters.method', 'all')
            ->where('filters.type', 'all')
        );
    }

    public function test_payments_report_loads_work_order_and_vehicle_without_column_errors(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();
        $center = Center::where('tenant_id', $tenant->id)->first();
        $customer = Customer::where('tenant_id', $tenant->id)->first();

        $make = VehicleMake::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'name_ar' => 'تويوتا',
            'name_en' => 'Toyota',
            'source' => 'system',
            'is_active' => true,
        ]);

        $model = VehicleModel::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'make_id' => $make->id,
            'name_ar' => 'كامري',
            'name_en' => 'Camry',
            'source' => 'system',
            'is_active' => true,
        ]);

        $vehicle = Vehicle::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'make_id' => $make->id,
            'model_id' => $model->id,
            'plate_number' => 'ABC 789',
            'year' => 2024,
        ]);

        $workOrder = WorkOrder::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'code' => 'WO-000099',
            'status' => 'open',
        ]);

        Payment::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'work_order_id' => $workOrder->id,
            'amount' => 500.00,
            'payment_date' => now(),
            'payment_method' => 'cash',
            'type' => 'payment',
            'reference' => 'REC-WO-099',
        ]);

        $response = $this->actingAs($user)->get('/app/reports/payments?search=WO-000099&range=all');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Reports/Payments')
            ->has('payments.data', 1)
            ->where('payments.data.0.work_order.code', 'WO-000099')
            ->where('payments.data.0.work_order.work_order_number', 'WO-000099')
        );

        $csvResponse = $this->actingAs($user)->get('/app/reports/payments/export?search=WO-000099&range=all');
        $csvResponse->assertStatus(200);
    }
}
