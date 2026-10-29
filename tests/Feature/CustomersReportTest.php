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
use App\Models\WorkOrderItem;
use Database\Seeders\PerformanceBaselineSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CustomersReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/app/reports/customers');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_customers_report(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        $response = $this->actingAs($user)->get('/app/reports/customers');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Reports/Customers')
            ->has('filters')
            ->has('kpis')
            ->has('records')
            ->has('centers')
            ->has('customers')
            ->has('makes')
            ->has('models')
            ->has('companyInfo')
        );
    }

    public function test_customers_report_kpis_and_financial_calculations(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $center = Center::where('tenant_id', $tenant->id)->first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'name' => 'شركة الأفق للاختبار',
            'phone' => '0501112233',
            'type' => 'company',
        ]);

        $vehicle = Vehicle::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'plate_number' => 'أ ب ج 9999',
            'year' => 2024,
        ]);

        $wo = WorkOrder::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'code' => 'WO-CUST-KPI-1',
            'status' => 'done',
            'entry_date' => now()->toDateString(),
            'total_excl_tax' => 1000,
            'total_tax' => 150,
            'total_incl_tax' => 1150,
            'total_taxable_amount' => 1000,
        ]);

        WorkOrderItem::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'work_order_id' => $wo->id,
            'item_type' => 'service',
            'title' => 'فحص شامل وصيانة محرك',
            'qty' => 1,
            'unit_price' => 1050,
            'discount_type' => 'fixed',
            'discount_value' => 50,
            'discount_amount' => 50,
            'subtotal' => 1000,
            'vat_rate' => 15,
            'vat_amount' => 150,
            'total' => 1150,
            'status' => 'completed',
        ]);

        DB::table('work_orders')->where('id', $wo->id)->update([
            'total_excl_tax' => 1000,
            'total_tax' => 150,
            'total_incl_tax' => 1150,
        ]);

        Payment::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'work_order_id' => $wo->id,
            'amount' => 400,
            'payment_date' => now(),
            'payment_method' => 'mada',
            'type' => 'payment',
        ]);

        Payment::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'work_order_id' => $wo->id,
            'amount' => 100,
            'payment_date' => now(),
            'payment_method' => 'other',
            'type' => 'bad_debt',
        ]);

        $response = $this->actingAs($user)->get("/app/reports/customers?customer_id={$customer->id}&range=all");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Reports/Customers')
            ->where('kpis.total_records', 1)
            ->where('kpis.total_payment_due', 1150)
            ->where('kpis.total_amount_paid', 500)
            ->where('kpis.total_amount_adjusted', 50)
            ->where('kpis.total_payment_remaining', 650)
            ->where('kpis.total_bad_debt', 100)
        );
    }

    public function test_filtering_customers_report_by_payment_status(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $center = Center::where('tenant_id', $tenant->id)->first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();
        $vehicle = Vehicle::where('tenant_id', $tenant->id)->first();

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'name' => 'عميل السداد',
            'phone' => '0555555555',
            'type' => 'individual',
        ]);

        $wo = WorkOrder::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'code' => 'WO-PAID-01',
            'status' => 'done',
            'entry_date' => now()->toDateString(),
            'total_excl_tax' => 200,
            'total_tax' => 30,
            'total_incl_tax' => 230,
            'total_taxable_amount' => 200,
        ]);

        DB::table('work_orders')->where('id', $wo->id)->update([
            'total_excl_tax' => 200,
            'total_tax' => 30,
            'total_incl_tax' => 230,
        ]);

        Payment::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'work_order_id' => $wo->id,
            'amount' => 230,
            'payment_date' => now(),
            'payment_method' => 'cash',
            'type' => 'payment',
        ]);

        $response = $this->actingAs($user)->get('/app/reports/customers?payment_status=paid&range=all');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Reports/Customers')
            ->where('filters.payment_status', 'paid')
        );
    }

    public function test_customers_report_grouped_by_customer(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $center = Center::where('tenant_id', $tenant->id)->first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();
        $vehicle = Vehicle::where('tenant_id', $tenant->id)->first();

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'name' => 'مجموعة شركة الرياض',
            'phone' => '0567890123',
            'type' => 'company',
        ]);

        WorkOrder::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'code' => 'WO-GRP-CUST-1',
            'status' => 'in_progress',
            'entry_date' => now()->toDateString(),
            'total_excl_tax' => 300,
            'total_tax' => 45,
            'total_incl_tax' => 345,
            'total_taxable_amount' => 300,
        ]);

        $response = $this->actingAs($user)->get('/app/reports/customers?group_by=customer&range=all');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Reports/Customers')
            ->where('filters.group_by', 'customer')
            ->has('groupedRecords')
        );
    }

    public function test_export_customers_report_csv(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        $response = $this->actingAs($user)->get('/app/reports/customers/export?range=all');

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment;', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_tenant_data_isolation_in_customers_report(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenantA = Tenant::first();
        $tenantB = Tenant::create([
            'name' => 'مستأجر منافس',
            'slug' => 'competitor-tenant',
            'status' => 'active',
        ]);

        $centerB = Center::create([
            'tenant_id' => $tenantB->id,
            'name' => 'مركز المستأجر الثاني',
            'slug' => 'competitor-center',
            'status' => 'active',
        ]);

        $customerB = Customer::create([
            'tenant_id' => $tenantB->id,
            'center_id' => $centerB->id,
            'name' => 'عميل خاص بمستأجر آخر',
            'phone' => '0599999999',
            'type' => 'individual',
        ]);

        $vehicleB = Vehicle::create([
            'tenant_id' => $tenantB->id,
            'center_id' => $centerB->id,
            'customer_id' => $customerB->id,
            'plate_number' => 'س ع د 8888',
            'year' => 2023,
        ]);

        WorkOrder::create([
            'tenant_id' => $tenantB->id,
            'center_id' => $centerB->id,
            'customer_id' => $customerB->id,
            'vehicle_id' => $vehicleB->id,
            'code' => 'WO-TENANT-B-SECRET',
            'status' => 'open',
            'entry_date' => now()->toDateString(),
            'total_excl_tax' => 9999,
            'total_tax' => 1499.85,
            'total_incl_tax' => 11498.85,
            'total_taxable_amount' => 9999,
        ]);

        $userA = User::where('tenant_id', $tenantA->id)->where('is_system_admin', true)->first();

        $response = $this->actingAs($userA)->get('/app/reports/customers?range=all&search=WO-TENANT-B-SECRET');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Reports/Customers')
            ->where('kpis.total_records', 0)
        );
    }
}
