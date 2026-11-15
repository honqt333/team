<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DashboardFinancialMetricsTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenantA;

    private Tenant $tenantB;

    private Center $centerA1;

    private Center $centerA2;

    private Center $centerB1;

    private User $userA;

    private Customer $customerA1;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Carbon::setTestNow(Carbon::parse('2026-11-15 12:00:00', 'UTC'));

        $this->tenantA = Tenant::factory()->create(['status' => 'active']);
        $this->tenantB = Tenant::factory()->create(['status' => 'active']);

        $this->centerA1 = Center::factory()->create(['tenant_id' => $this->tenantA->id, 'is_active' => true]);
        $this->centerA2 = Center::factory()->create(['tenant_id' => $this->tenantA->id, 'is_active' => true]);
        $this->centerB1 = Center::factory()->create(['tenant_id' => $this->tenantB->id, 'is_active' => true]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        setPermissionsTeamId($this->tenantA->id);

        $role = Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'web',
            'tenant_id' => $this->tenantA->id,
        ]);

        $this->userA = User::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'current_center_id' => $this->centerA1->id,
        ]);
        $this->userA->assignRole($role);
        $this->userA->centers()->attach([
            $this->centerA1->id => ['tenant_id' => $this->tenantA->id],
            $this->centerA2->id => ['tenant_id' => $this->tenantA->id],
        ]);

        $this->customerA1 = Customer::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA1->id,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_revenue_accounts_for_full_partial_and_cancelled_credit_notes(): void
    {
        // Invoice 115 without credit note
        Invoice::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA1->id,
            'customer_id' => $this->customerA1->id,
            'type' => 'invoice',
            'status' => 'issued',
            'issue_date' => now(),
            'total_incl_tax' => 115.00,
        ]);

        // Invoice 115 with full credit note 115
        Invoice::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA1->id,
            'customer_id' => $this->customerA1->id,
            'type' => 'invoice',
            'status' => 'issued',
            'issue_date' => now(),
            'total_incl_tax' => 115.00,
        ]);
        Invoice::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA1->id,
            'customer_id' => $this->customerA1->id,
            'type' => 'credit_note',
            'status' => 'issued',
            'issue_date' => now(),
            'total_incl_tax' => 115.00,
        ]);

        // Partial credit note 50 and cancelled credit note 90 (cancelled must NOT be deducted)
        Invoice::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA1->id,
            'customer_id' => $this->customerA1->id,
            'type' => 'credit_note',
            'status' => 'issued',
            'issue_date' => now(),
            'total_incl_tax' => 50.00,
        ]);
        Invoice::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA1->id,
            'customer_id' => $this->customerA1->id,
            'type' => 'credit_note',
            'status' => 'cancelled',
            'issue_date' => now(),
            'total_incl_tax' => 90.00,
        ]);

        // Net sales = (115 + 115) - (115 + 50) = 65.00
        $resp = $this->actingAs($this->userA)->get('/dashboard?period=month');
        $resp->assertOk();
        $rev = $resp->viewData('page')['props']['stats']['revenue'];

        $this->assertEquals(65.00, $rev['thisMonth']);
        $this->assertEquals(65.00, $rev['periodTotal']);
    }

    public function test_payments_today_subtracts_refunds_excludes_bad_debt_soft_deleted_and_other_centers(): void
    {
        $invA1 = Invoice::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA1->id,
            'customer_id' => $this->customerA1->id,
            'type' => 'invoice',
            'status' => 'issued',
            'payment_status' => 'partial',
            'issue_date' => now(),
            'total_incl_tax' => 500.00,
        ]);

        // Cash payment 115
        Payment::forceCreate([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA1->id,
            'invoice_id' => $invA1->id,
            'amount' => 115.00,
            'type' => Payment::TYPE_PAYMENT,
            'payment_method' => 'cash',
            'payment_date' => now(),
        ]);

        // Refund 115 (must subtract from net cash collections, NOT add!)
        Payment::forceCreate([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA1->id,
            'invoice_id' => $invA1->id,
            'amount' => 115.00,
            'type' => Payment::TYPE_REFUND,
            'payment_method' => 'cash',
            'payment_date' => now(),
        ]);

        // Another payment 200
        Payment::forceCreate([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA1->id,
            'invoice_id' => $invA1->id,
            'amount' => 200.00,
            'type' => Payment::TYPE_PAYMENT,
            'payment_method' => 'card',
            'payment_date' => now(),
        ]);

        // Bad debt 50 (not cash collected)
        Payment::forceCreate([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA1->id,
            'invoice_id' => $invA1->id,
            'amount' => 50.00,
            'type' => Payment::TYPE_BAD_DEBT,
            'payment_method' => 'cash',
            'payment_date' => now(),
        ]);

        // Soft-deleted payment 300 (must be excluded)
        $deleted = Payment::forceCreate([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA1->id,
            'invoice_id' => $invA1->id,
            'amount' => 300.00,
            'type' => Payment::TYPE_PAYMENT,
            'payment_method' => 'cash',
            'payment_date' => now(),
        ]);
        $deleted->delete();

        // Other center payment 400 (must be excluded)
        Payment::forceCreate([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA2->id,
            'invoice_id' => $invA1->id,
            'amount' => 400.00,
            'type' => Payment::TYPE_PAYMENT,
            'payment_method' => 'cash',
            'payment_date' => now(),
        ]);

        // Other tenant payment 900 (must be excluded)
        Payment::forceCreate([
            'tenant_id' => $this->tenantB->id,
            'center_id' => $this->centerB1->id,
            'invoice_id' => $invA1->id,
            'amount' => 900.00,
            'type' => Payment::TYPE_PAYMENT,
            'payment_method' => 'cash',
            'payment_date' => now(),
        ]);

        $resp = $this->actingAs($this->userA)->get('/dashboard?period=month');
        $resp->assertOk();
        $rev = $resp->viewData('page')['props']['stats']['revenue'];

        // Net cash collections today = 115 - 115 + 200 = 200.00
        $this->assertEquals(200.00, $rev['paymentsToday']);
    }

    public function test_outstanding_balance_respects_soft_deletes_refunds_and_bad_debt(): void
    {
        $inv = Invoice::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA1->id,
            'customer_id' => $this->customerA1->id,
            'type' => 'invoice',
            'status' => 'issued',
            'payment_status' => 'partial',
            'issue_date' => now(),
            'total_incl_tax' => 500.00,
        ]);

        // Paid 200
        Payment::forceCreate([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA1->id,
            'invoice_id' => $inv->id,
            'amount' => 200.00,
            'type' => Payment::TYPE_PAYMENT,
            'payment_method' => 'cash',
            'payment_date' => now(),
        ]);

        // Refunded 50 => net paid drops to 150
        Payment::forceCreate([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA1->id,
            'invoice_id' => $inv->id,
            'amount' => 50.00,
            'type' => Payment::TYPE_REFUND,
            'payment_method' => 'cash',
            'payment_date' => now(),
        ]);

        // Bad debt 25 => reduces remaining balance to 500 - (200 - 50 + 25) = 325
        Payment::forceCreate([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA1->id,
            'invoice_id' => $inv->id,
            'amount' => 25.00,
            'type' => Payment::TYPE_BAD_DEBT,
            'payment_method' => 'cash',
            'payment_date' => now(),
        ]);

        // Soft-deleted payment 100 => must NOT reduce balance
        $deleted = Payment::forceCreate([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA1->id,
            'invoice_id' => $inv->id,
            'amount' => 100.00,
            'type' => Payment::TYPE_PAYMENT,
            'payment_method' => 'cash',
            'payment_date' => now(),
        ]);
        $deleted->delete();

        $resp = $this->actingAs($this->userA)->get('/dashboard?period=month');
        $resp->assertOk();
        $rev = $resp->viewData('page')['props']['stats']['revenue'];

        $this->assertEquals(325.00, $rev['outstanding']);
    }

    public function test_previous_period_revenue_includes_full_last_day_after_midnight(): void
    {
        // Current date is 2026-11-15. For period=month, previous period is 2026-10-01 00:00:00 .. 2026-10-31 23:59:59.
        // An invoice on 2026-10-31 18:45:00 was previously excluded by whereBetween(..., ['2026-10-01', '2026-10-31']).
        Invoice::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA1->id,
            'customer_id' => $this->customerA1->id,
            'type' => 'invoice',
            'status' => 'issued',
            'issue_date' => Carbon::parse('2026-10-31 18:45:00', 'UTC'),
            'total_incl_tax' => 230.00,
        ]);

        // Multi-center invoice in A2 on same date must stay isolated
        Invoice::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA2->id,
            'customer_id' => $this->customerA1->id,
            'type' => 'invoice',
            'status' => 'issued',
            'issue_date' => Carbon::parse('2026-10-31 18:45:00', 'UTC'),
            'total_incl_tax' => 999.00,
        ]);

        $resp = $this->actingAs($this->userA)->get('/dashboard?period=month');
        $resp->assertOk();
        $rev = $resp->viewData('page')['props']['stats']['revenue'];

        $this->assertEquals(230.00, $rev['prevPeriodTotal']);
        $this->assertEquals(230.00, $rev['lastMonth']);
    }
}
