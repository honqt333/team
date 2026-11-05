<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PerformanceBaselineSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashRegisterReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/app/reports/cash-register');
        $response->assertRedirect('/login');
    }

    public function test_wrshah_legacy_redirects(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        $res1 = $this->actingAs($user)->get('/reports/cashRegister');
        $res1->assertRedirect(route('app.reports.cash-register'));

        $res2 = $this->actingAs($user)->get('/app/reports/cashRegister');
        $res2->assertRedirect(route('app.reports.cash-register'));
    }

    public function test_authenticated_user_can_view_cash_register_report(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        $response = $this->actingAs($user)->get('/app/reports/cash-register');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Reports/CashRegister')
            ->has('filters')
            ->has('kpis')
            ->has('items')
            ->has('centers')
            ->has('users')
            ->has('companyInfo')
        );
    }

    public function test_cash_register_calculates_debit_credit_and_balance_accurately(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $center = Center::create([
            'tenant_id' => $tenant->id,
            'name' => 'فرع حركة الصندوق التجريبي',
            'slug' => 'cash-register-'.uniqid(),
            'address' => 'جدة',
        ]);
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'name' => 'عميل الصندوق',
            'phone' => '0551122334',
            'type' => 'individual',
        ]);

        $invoice = Invoice::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-CR-001',
            'type' => 'invoice',
            'status' => 'valid',
            'issue_date' => now(),
            'supply_date' => now(),
            'total_excl_tax' => 1000.00,
            'total_tax' => 150.00,
            'total_incl_tax' => 1150.00,
            'total_paid' => 1150.00,
        ]);

        // Inflow 1: Customer payment (Cash, 800 SAR)
        Payment::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'invoice_id' => $invoice->id,
            'amount' => 800.00,
            'type' => 'payment',
            'payment_date' => now(),
            'payment_method' => 'cash',
            'reference' => 'PAY-CR-01',
            'received_by' => $user->id,
        ]);

        // Inflow 2: Customer payment (Mada card, 350 SAR)
        Payment::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'invoice_id' => $invoice->id,
            'amount' => 350.00,
            'type' => 'payment',
            'payment_date' => now(),
            'payment_method' => 'mada',
            'reference' => 'PAY-CR-02',
            'received_by' => $user->id,
        ]);

        // Outflow 1: Customer refund (Cash, 200 SAR)
        Payment::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'invoice_id' => $invoice->id,
            'amount' => 200.00,
            'type' => 'refund',
            'payment_date' => now(),
            'payment_method' => 'cash',
            'reference' => 'REF-CR-01',
            'received_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get('/app/reports/cash-register?range=this_month&center_id='.$center->id);

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Reports/CashRegister')
            ->where('kpis.total_records', 3)
            ->where('kpis.total_debit', 1150)
            ->where('kpis.total_credit', 200)
            ->where('kpis.net_balance', 950)
            ->where('kpis.cash_balance', 600) // 800 - 200
            ->where('kpis.electronic_balance', 350) // 350 mada
        );
    }

    public function test_cash_register_filters_by_payment_method(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $center = Center::create([
            'tenant_id' => $tenant->id,
            'name' => 'فرع التصفية',
            'slug' => 'cr-method-'.uniqid(),
            'address' => 'الدمام',
        ]);
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        // 1 cash payment
        Payment::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'amount' => 500.00,
            'type' => 'payment',
            'payment_date' => now(),
            'payment_method' => 'cash',
            'reference' => 'PAY-CASH-ONLY',
            'received_by' => $user->id,
        ]);

        // 1 transfer payment
        Payment::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'amount' => 1200.00,
            'type' => 'payment',
            'payment_date' => now(),
            'payment_method' => 'transfer',
            'reference' => 'PAY-TRANSFER-ONLY',
            'received_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get('/app/reports/cash-register?range=this_month&center_id='.$center->id.'&payment_method=cash');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Reports/CashRegister')
            ->where('kpis.total_records', 1)
            ->where('kpis.total_debit', 500)
            ->where('items.data.0.ref_no', 'PAY-CASH-ONLY')
        );
    }

    public function test_cash_register_exports_csv_successfully(): void
    {
        app(PerformanceBaselineSeeder::class)->run();

        $tenant = Tenant::first();
        $user = User::where('tenant_id', $tenant->id)->where('is_system_admin', true)->first();

        $response = $this->actingAs($user)->get('/app/reports/cash-register/export?range=this_month');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringStartsWith(chr(0xEF).chr(0xBB).chr(0xBF), $content);
        $this->assertStringContainsString('رقم المرجع', $content);
        $this->assertStringContainsString('المبلغ المقبوض (وارد)', $content);
        $this->assertStringContainsString('المبلغ المدفوع (صادر)', $content);
    }
}
