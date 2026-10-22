<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
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
}
