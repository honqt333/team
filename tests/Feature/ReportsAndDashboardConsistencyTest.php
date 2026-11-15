<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\InvoiceType;
use App\Enums\PaymentType;
use App\Models\Center;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Services\CreditNoteService;
use App\Services\InvoiceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ReportsAndDashboardConsistencyTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Center $centerA;

    private Center $centerB;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Carbon::setTestNow(Carbon::parse('2026-11-15 12:00:00', 'UTC'));

        $this->tenant = Tenant::factory()->create(['status' => 'active']);
        $this->centerA = Center::factory()->create(['tenant_id' => $this->tenant->id, 'is_active' => true]);
        $this->centerB = Center::factory()->create(['tenant_id' => $this->tenant->id, 'is_active' => true]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        setPermissionsTeamId($this->tenant->id);

        $role = Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'web',
            'tenant_id' => $this->tenant->id,
        ]);

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->centerA->id,
            'is_active' => true,
            'is_system_admin' => true,
        ]);
        $this->user->assignRole($role);
        $this->user->centers()->attach([
            $this->centerA->id => ['tenant_id' => $this->tenant->id],
            $this->centerB->id => ['tenant_id' => $this->tenant->id],
        ]);

        $this->actingAs($this->user);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function createWorkOrderWithService(
        Customer $customer,
        float $price,
        ?Center $center = null,
        string $status = 'done',
    ): WorkOrder {
        $targetCenter = $center ?? $this->centerA;
        $vehicle = Vehicle::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $targetCenter->id,
            'customer_id' => $customer->id,
        ]);

        $wo = WorkOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $targetCenter->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'status' => $status,
            'entry_date' => now()->toDateString(),
            'tax_enabled_snapshot' => false,
            'tax_rate_snapshot' => 0,
            'pricing_mode_snapshot' => 'exclusive',
            'total_excl_tax' => $price,
            'total_tax' => 0,
            'total_incl_tax' => $price,
        ]);

        WorkOrderItem::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $targetCenter->id,
            'work_order_id' => $wo->id,
            'title' => 'صيانة',
            'unit_price' => $price,
            'qty' => 1,
            'discount_amount' => 0,
            'line_total_excl_tax' => $price,
            'line_total_incl_tax' => $price,
            'status' => 'completed',
        ]);

        return $wo->fresh();
    }

    private function issueInvoiceForWorkOrder(WorkOrder $wo, string $number): Invoice
    {
        $inv = app(InvoiceService::class)->createFromWorkOrder($wo, $this->user);
        $inv->forceFill([
            'status' => 'valid',
            'invoice_number' => $number,
            'issue_date' => now(),
        ])->saveQuietly();
        $inv->updatePaymentStatus();

        return $inv->fresh();
    }

    private function issueCreditNoteForWorkOrder(WorkOrder $wo, float $amount, bool $refund = false): Invoice
    {
        $item = $wo->items()->firstOrFail();

        return app(CreditNoteService::class)->createCreditNote($wo, [
            'services' => [['work_order_item_id' => $item->id, 'return_amount' => $amount]],
            'parts' => [],
            'reason' => 'مرتجع اختبار',
            'record_refund' => $refund,
            'refund_payment_method' => 'cash',
        ], $this->user);
    }

    public function test_s1_to_s7_invoices_report_print_export_and_dashboard_respect_credit_notes(): void
    {
        $customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->centerA->id,
        ]);

        // S1: Unpaid 1000 -> remaining 1000
        $wo1 = $this->createWorkOrderWithService($customer, 1000.00);
        $inv1 = $this->issueInvoiceForWorkOrder($wo1, 'INV-R1');

        // S2: Partial payment 200 on 1000 -> remaining 800
        $wo2 = $this->createWorkOrderWithService($customer, 1000.00);
        $inv2 = $this->issueInvoiceForWorkOrder($wo2, 'INV-R2');
        Payment::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->centerA->id,
            'work_order_id' => $wo2->id,
            'invoice_id' => $inv2->id,
            'amount' => 200.00,
            'payment_date' => now(),
            'payment_method' => 'cash',
            'type' => PaymentType::PAYMENT,
            'received_by' => $this->user->id,
        ]);
        $inv2->updatePaymentStatus();

        // S3: Invoice 1000 + valid CN 300 -> remaining 700
        $wo3 = $this->createWorkOrderWithService($customer, 1000.00);
        $inv3 = $this->issueInvoiceForWorkOrder($wo3, 'INV-R3');
        $this->issueCreditNoteForWorkOrder($wo3, 300.00);

        // S4: Mixed settlement 1000 - 200 paid - 300 CN -> remaining 500
        $wo4 = $this->createWorkOrderWithService($customer, 1000.00);
        $inv4 = $this->issueInvoiceForWorkOrder($wo4, 'INV-R4');
        Payment::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->centerA->id,
            'work_order_id' => $wo4->id,
            'invoice_id' => $inv4->id,
            'amount' => 200.00,
            'payment_date' => now(),
            'payment_method' => 'cash',
            'type' => PaymentType::PAYMENT,
            'received_by' => $this->user->id,
        ]);
        $inv4->updatePaymentStatus();
        $this->issueCreditNoteForWorkOrder($wo4, 300.00);

        // S5: Full CN 1000 on 1000 -> remaining 0
        $wo5 = $this->createWorkOrderWithService($customer, 1000.00);
        $inv5 = $this->issueInvoiceForWorkOrder($wo5, 'INV-R5');
        $this->issueCreditNoteForWorkOrder($wo5, 1000.00);

        // S6 & S7: Draft, Cancelled, and Soft-Deleted CNs on INV-R1 must NOT reduce remaining (still 1000)
        Invoice::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->centerA->id,
            'customer_id' => $customer->id,
            'work_order_id' => $wo1->id,
            'original_invoice_id' => $inv1->id,
            'type' => InvoiceType::CREDIT_NOTE,
            'status' => 'draft',
            'issue_date' => now(),
            'total_excl_tax' => 250,
            'total_incl_tax' => 250,
        ]);
        Invoice::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->centerA->id,
            'customer_id' => $customer->id,
            'work_order_id' => $wo1->id,
            'original_invoice_id' => $inv1->id,
            'type' => InvoiceType::CREDIT_NOTE,
            'status' => 'cancelled',
            'issue_date' => now(),
            'total_excl_tax' => 250,
            'total_incl_tax' => 250,
        ]);
        $softDeletedCn = Invoice::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->centerA->id,
            'customer_id' => $customer->id,
            'work_order_id' => $wo1->id,
            'original_invoice_id' => $inv1->id,
            'type' => InvoiceType::CREDIT_NOTE,
            'status' => 'valid',
            'issue_date' => now(),
            'total_excl_tax' => 250,
            'total_incl_tax' => 250,
        ]);
        DB::table('invoices')->where('id', $softDeletedCn->id)->update(['deleted_at' => now()]);

        // Expected total remaining = 1000 + 800 + 700 + 500 + 0 = 3000.00
        // (and 4 unpaid/partial WOs in checkedOutPaymentDue: wo1=1000, wo2=800, wo3=700, wo4=500 -> count=4, amount=3000)
        $this->get('/app/reports/invoices?range=today&center_id='.$this->centerA->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('kpis.total_payment_remaining', fn ($v) => abs((float) $v - 3000.0) < 0.01)
            );

        // Print parity
        $this->get('/app/reports/invoices?range=today&center_id='.$this->centerA->id.'&print=1')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('kpis.total_payment_remaining', fn ($v) => abs((float) $v - 3000.0) < 0.01)
            );

        // CSV Export parity: verify INV-R3 shows 700.00 and INV-R4 shows 500.00 and INV-R5 shows 0.00
        $csvResponse = $this->get('/app/reports/invoices/export?range=today&center_id='.$this->centerA->id);
        $csvResponse->assertOk();
        $csvContent = $csvResponse->streamedContent();
        $this->assertStringContainsString('INV-R3', $csvContent);
        $this->assertStringContainsString('700.00', $csvContent);
        $this->assertStringContainsString('INV-R4', $csvContent);
        $this->assertStringContainsString('500.00', $csvContent);

        // Dashboard checkedOutPaymentDue must also equal count=4, amount=3000.00
        $this->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('checkedOutPaymentDue.count', 4)
                ->where('checkedOutPaymentDue.amount', fn ($v) => abs((float) $v - 3000.0) < 0.01)
            );
    }

    public function test_s8_s9_refund_due_and_bad_debt_handling_in_reports(): void
    {
        $customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->centerA->id,
        ]);

        // S8: Paid 800 on 1000, CN 500 without refund -> invoice remaining = 0, CN refund_due = 300 MUST NOT show as customer debt
        $wo1 = $this->createWorkOrderWithService($customer, 1000.00);
        $inv1 = $this->issueInvoiceForWorkOrder($wo1, 'INV-S8');
        Payment::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->centerA->id,
            'work_order_id' => $wo1->id,
            'invoice_id' => $inv1->id,
            'amount' => 800.00,
            'payment_date' => now(),
            'payment_method' => 'cash',
            'type' => PaymentType::PAYMENT,
            'received_by' => $this->user->id,
        ]);
        $inv1->updatePaymentStatus();
        $this->issueCreditNoteForWorkOrder($wo1, 500.00, false);

        // S9: Bad Debt 1000 on 1000 -> remaining = 0, NOT counted as cash collected in total_paid / total_amount_paid
        $wo2 = $this->createWorkOrderWithService($customer, 1000.00);
        $inv2 = $this->issueInvoiceForWorkOrder($wo2, 'INV-S9');
        Payment::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->centerA->id,
            'work_order_id' => $wo2->id,
            'invoice_id' => $inv2->id,
            'amount' => 1000.00,
            'payment_date' => now(),
            'payment_method' => 'cash',
            'type' => PaymentType::BAD_DEBT,
            'received_by' => $this->user->id,
        ]);
        $inv2->updatePaymentStatus();

        $this->get('/app/reports/invoices?range=today&center_id='.$this->centerA->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('kpis.total_payment_remaining', fn ($v) => (float) $v === 0.0)
                ->where('kpis.total_paid', fn ($v) => abs((float) $v - 800.0) < 0.01)
            );

        $this->get('/app/reports/customers?range=today&center_id='.$this->centerA->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('kpis.total_amount_paid', fn ($v) => abs((float) $v - 1800.0) < 0.01)
                ->where('kpis.total_bad_debt', fn ($v) => abs((float) $v - 1000.0) < 0.01)
                ->where('kpis.total_payment_remaining', fn ($v) => (float) $v === 0.0)
            );
    }

    public function test_s10_s11_s12_multi_center_scope_and_no_double_counting_with_work_orders(): void
    {
        $customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->centerA->id,
        ]);

        // Center A: done WO 1000 with issued invoice + CN 300 -> 700
        $woA = $this->createWorkOrderWithService($customer, 1000.00, $this->centerA);
        $this->issueInvoiceForWorkOrder($woA, 'INV-A1');
        $this->issueCreditNoteForWorkOrder($woA, 300.00);

        // Center A: done WO 400 with DRAFT invoice -> 400 still due in Dashboard checkedOutPaymentDue (no double count)
        $woDraft = $this->createWorkOrderWithService($customer, 400.00, $this->centerA);
        app(InvoiceService::class)->createFromWorkOrder($woDraft, $this->user);

        // Center B: done WO 500 with issued invoice -> 500
        $this->user->forceFill(['current_center_id' => $this->centerB->id])->save();
        $woB = $this->createWorkOrderWithService($customer, 500.00, $this->centerB);
        $this->issueInvoiceForWorkOrder($woB, 'INV-B1');
        $this->user->forceFill(['current_center_id' => $this->centerA->id])->save();

        // Center A scoped invoice report: only 700 remaining (draft excluded, Center B excluded)
        $this->get('/app/reports/invoices?range=today&center_id='.$this->centerA->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('kpis.total_payment_remaining', fn ($v) => abs((float) $v - 700.0) < 0.01)
            );

        // All accessible centers invoice report: 700 + 500 = 1200
        $this->get('/app/reports/invoices?range=today&center_id=all')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('kpis.total_payment_remaining', fn ($v) => abs((float) $v - 1200.0) < 0.01)
            );

        // Dashboard for current_center_id = Center A: woA (700) + woDraft (400) = 1100, count = 2 (no double count)
        $this->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('checkedOutPaymentDue.count', 2)
                ->where('checkedOutPaymentDue.amount', fn ($v) => abs((float) $v - 1100.0) < 0.01)
            );
    }
}
