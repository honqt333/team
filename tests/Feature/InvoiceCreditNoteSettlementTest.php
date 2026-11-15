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
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class InvoiceCreditNoteSettlementTest extends TestCase
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

        $this->actingAs($this->userA);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function createIssuedInvoice(float $total = 1000.00): Invoice
    {
        return Invoice::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA1->id,
            'customer_id' => $this->customerA1->id,
            'type' => 'invoice',
            'status' => 'issued',
            'payment_status' => 'unpaid',
            'issue_date' => now(),
            'total_excl_tax' => round($total / 1.15, 2),
            'total_tax' => round($total - ($total / 1.15), 2),
            'total_incl_tax' => $total,
            'total_paid' => 0,
        ]);
    }

    private function dashboardOutstanding(): float
    {
        $resp = $this->actingAs($this->userA)->get('/dashboard?period=month');
        $resp->assertOk();

        return (float) $resp->viewData('page')['props']['stats']['revenue']['outstanding'];
    }

    public function test_s1_to_s7_settlement_and_dashboard_outstanding_scenarios(): void
    {
        // S1: Invoice 1000, paid 0, credit note 0 => outstanding 1000, unpaid
        $inv1 = $this->createIssuedInvoice(1000.00);
        $inv1->updatePaymentStatus();
        $inv1->refresh();
        $this->assertSame('unpaid', $inv1->payment_status);
        $this->assertEquals(1000.00, $this->dashboardOutstanding());

        // S2: Invoice 1000, paid 200, credit note 0 => outstanding 800, partial
        Payment::forceCreate([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA1->id,
            'invoice_id' => $inv1->id,
            'amount' => 200.00,
            'type' => Payment::TYPE_PAYMENT,
            'payment_method' => 'cash',
            'payment_date' => now(),
        ]);
        $inv1->updatePaymentStatus();
        $inv1->refresh();
        $this->assertSame('partial', $inv1->payment_status);
        $this->assertEquals(200.00, (float) $inv1->total_paid);
        $this->assertEquals(800.00, $this->dashboardOutstanding());

        // S3: Invoice 1000, paid 0, credit note 300 => outstanding 700, partial
        DB::table('payments')->delete();
        DB::table('invoices')->delete();
        $inv3 = $this->createIssuedInvoice(1000.00);
        Invoice::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA1->id,
            'customer_id' => $this->customerA1->id,
            'original_invoice_id' => $inv3->id,
            'type' => 'credit_note',
            'status' => 'valid',
            'payment_status' => 'paid',
            'issue_date' => now(),
            'total_incl_tax' => 300.00,
            'total_paid' => 300.00,
        ]);
        $inv3->updatePaymentStatus();
        $inv3->refresh();
        $this->assertSame('partial', $inv3->payment_status);
        $this->assertEquals(0.00, (float) $inv3->total_paid);
        $this->assertEquals(1000.00, (float) $inv3->total_incl_tax);
        $this->assertEquals(700.00, $this->dashboardOutstanding());

        // S4: Invoice 1000, paid 200, credit note 300 => outstanding 500, partial
        Payment::forceCreate([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA1->id,
            'invoice_id' => $inv3->id,
            'amount' => 200.00,
            'type' => Payment::TYPE_PAYMENT,
            'payment_method' => 'cash',
            'payment_date' => now(),
        ]);
        $inv3->updatePaymentStatus();
        $inv3->refresh();
        $this->assertSame('partial', $inv3->payment_status);
        $this->assertEquals(200.00, (float) $inv3->total_paid);
        $this->assertEquals(500.00, $this->dashboardOutstanding());

        // S5: Invoice 1000, paid 0, full credit note 1000 => outstanding 0, paid
        DB::table('payments')->delete();
        DB::table('invoices')->delete();
        $inv5 = $this->createIssuedInvoice(1000.00);
        $cn5 = Invoice::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA1->id,
            'customer_id' => $this->customerA1->id,
            'original_invoice_id' => $inv5->id,
            'type' => 'credit_note',
            'status' => 'valid',
            'payment_status' => 'paid',
            'issue_date' => now(),
            'total_incl_tax' => 1000.00,
            'total_paid' => 1000.00,
        ]);
        $inv5->updatePaymentStatus();
        $inv5->refresh();
        $this->assertSame('paid', $inv5->payment_status);
        $this->assertEquals(0.00, $this->dashboardOutstanding());

        // Idempotent repeat of updatePaymentStatus()
        $inv5->updatePaymentStatus();
        $inv5->refresh();
        $this->assertSame('paid', $inv5->payment_status);
        $this->assertEquals(0.00, $this->dashboardOutstanding());

        // S6: Cancelled credit note 1000 => outstanding returns to 1000, unpaid
        $cn5->forceFill(['status' => 'cancelled'])->save();
        $inv5->refresh();
        $this->assertSame('unpaid', $inv5->payment_status);
        $this->assertEquals(1000.00, $this->dashboardOutstanding());

        // S7: Invoice 1000, paid 1000, credit note 300 with refund 300 on credit note => outstanding 0, paid
        DB::table('payments')->delete();
        DB::table('invoices')->delete();
        $inv7 = $this->createIssuedInvoice(1000.00);
        Payment::forceCreate([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA1->id,
            'invoice_id' => $inv7->id,
            'amount' => 1000.00,
            'type' => Payment::TYPE_PAYMENT,
            'payment_method' => 'cash',
            'payment_date' => now(),
        ]);
        $cn7 = Invoice::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA1->id,
            'customer_id' => $this->customerA1->id,
            'original_invoice_id' => $inv7->id,
            'type' => 'credit_note',
            'status' => 'valid',
            'payment_status' => 'paid',
            'issue_date' => now(),
            'total_incl_tax' => 300.00,
            'total_paid' => 300.00,
        ]);
        Payment::forceCreate([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA1->id,
            'invoice_id' => $cn7->id,
            'amount' => 300.00,
            'type' => Payment::TYPE_REFUND,
            'payment_method' => 'cash',
            'payment_date' => now(),
        ]);
        $inv7->updatePaymentStatus();
        $inv7->refresh();
        $this->assertSame('paid', $inv7->payment_status);
        $this->assertEquals(1000.00, (float) $inv7->total_paid);
        $this->assertEquals(0.00, $this->dashboardOutstanding());
    }

    public function test_multiple_partial_draft_soft_deleted_bad_debt_and_cross_scope_isolation(): void
    {
        $inv = $this->createIssuedInvoice(1000.00);

        // Two valid partial credit notes: 150 + 250 = 400
        Invoice::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA1->id,
            'customer_id' => $this->customerA1->id,
            'original_invoice_id' => $inv->id,
            'type' => 'credit_note',
            'status' => 'valid',
            'issue_date' => now(),
            'total_incl_tax' => 150.00,
        ]);
        Invoice::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA1->id,
            'customer_id' => $this->customerA1->id,
            'original_invoice_id' => $inv->id,
            'type' => 'credit_note',
            'status' => 'issued',
            'issue_date' => now(),
            'total_incl_tax' => 250.00,
        ]);

        // Draft credit note 200 (must NOT count)
        $draftCn = Invoice::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA1->id,
            'customer_id' => $this->customerA1->id,
            'original_invoice_id' => $inv->id,
            'type' => 'credit_note',
            'status' => 'draft',
            'issue_date' => now(),
            'total_incl_tax' => 200.00,
        ]);

        // Soft-deleted draft credit note 100 (must NOT count)
        $deletedCn = Invoice::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA1->id,
            'customer_id' => $this->customerA1->id,
            'original_invoice_id' => $inv->id,
            'type' => 'credit_note',
            'status' => 'draft',
            'issue_date' => now(),
            'total_incl_tax' => 100.00,
        ]);
        $deletedCn->delete();
        // Keep unused variable referenced cleanly
        $this->assertSame('draft', $draftCn->status);

        // Cross-center credit note 500 (must NOT affect center A1 invoice)
        Invoice::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA2->id,
            'customer_id' => $this->customerA1->id,
            'original_invoice_id' => $inv->id,
            'type' => 'credit_note',
            'status' => 'valid',
            'issue_date' => now(),
            'total_incl_tax' => 500.00,
        ]);

        // Cross-tenant credit note 500 (must NOT affect tenant A invoice)
        Invoice::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'center_id' => $this->centerB1->id,
            'customer_id' => $this->customerA1->id,
            'original_invoice_id' => $inv->id,
            'type' => 'credit_note',
            'status' => 'valid',
            'issue_date' => now(),
            'total_incl_tax' => 500.00,
        ]);

        // Bad debt 100 + soft-deleted payment 300
        Payment::forceCreate([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA1->id,
            'invoice_id' => $inv->id,
            'amount' => 100.00,
            'type' => Payment::TYPE_BAD_DEBT,
            'payment_method' => 'cash',
            'payment_date' => now(),
        ]);
        $deletedPay = Payment::forceCreate([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA1->id,
            'invoice_id' => $inv->id,
            'amount' => 300.00,
            'type' => Payment::TYPE_PAYMENT,
            'payment_method' => 'cash',
            'payment_date' => now(),
        ]);
        $deletedPay->delete();

        $inv->updatePaymentStatus();
        $inv->refresh();

        // 1000 - (150 + 250) - 100 = 500
        $this->assertSame('partial', $inv->payment_status);
        $this->assertEquals(100.00, (float) $inv->total_paid);
        $this->assertEquals(500.00, $this->dashboardOutstanding());
    }
}
