<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\InvoiceType;
use App\Enums\PaymentType;
use App\Models\Center;
use App\Models\Customer;
use App\Models\Dealer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Support\Permissions;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CustomerAndDealerReportsReconciliationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Center $centerA;

    private Center $centerB;

    private User $adminUser;

    private User $singleBranchUser;

    private Customer $customer;

    private Dealer $dealer;

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

        $this->adminUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->centerA->id,
            'is_active' => true,
            'is_system_admin' => true,
        ]);
        $this->adminUser->assignRole($role);
        $this->adminUser->centers()->attach([
            $this->centerA->id => ['tenant_id' => $this->tenant->id],
            $this->centerB->id => ['tenant_id' => $this->tenant->id],
        ]);

        $permViewPayments = Permission::findOrCreate(Permissions::PAYMENTS_VIEW, 'web');
        $permViewDealers = Permission::findOrCreate('dealers.view', 'web');
        $this->singleBranchUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->centerA->id,
            'is_active' => true,
            'is_system_admin' => false,
        ]);
        $this->singleBranchUser->givePermissionTo([$permViewPayments, $permViewDealers]);
        $this->singleBranchUser->centers()->attach([
            $this->centerA->id => ['tenant_id' => $this->tenant->id],
        ]);

        $this->customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->centerA->id,
        ]);

        $this->dealer = Dealer::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'تاجر التوافق',
            'type' => 'corporate',
            'phone' => '0550001111',
            'is_active' => true,
        ]);

        $this->actingAs($this->adminUser);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function createDoneWorkOrder(
        float $totalInclTax,
        ?Center $center = null,
        ?Dealer $dealer = null,
        ?Customer $customer = null,
    ): WorkOrder {
        $targetCenter = $center ?? $this->centerA;
        $targetCustomer = $customer ?? $this->customer;
        $vehicle = Vehicle::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $targetCenter->id,
            'customer_id' => $targetCustomer->id,
        ]);

        $wo = new WorkOrder;
        $wo->forceFill([
            'tenant_id' => $this->tenant->id,
            'center_id' => $targetCenter->id,
            'customer_id' => $targetCustomer->id,
            'vehicle_id' => $vehicle->id,
            'dealer_id' => $dealer?->id,
            'is_paid_by_dealer' => $dealer !== null,
            'code' => 'WO-'.random_int(10000, 99999),
            'status' => 'done',
            'entry_date' => now()->toDateString(),
            'total_excl_tax' => round($totalInclTax / 1.15, 2),
            'total_tax' => round($totalInclTax - ($totalInclTax / 1.15), 2),
            'total_incl_tax' => $totalInclTax,
        ]);
        $wo->saveQuietly();

        return $wo;
    }

    private function createInvoice(
        WorkOrder $wo,
        string $number,
        string $type = 'invoice',
        string $status = 'valid',
        ?float $total = null,
        ?int $originalInvoiceId = null,
        ?int $workOrderIdOverride = null,
        bool $useNullWorkOrderId = false,
    ): Invoice {
        $amount = $total ?? (float) $wo->total_incl_tax;

        return Invoice::create([
            'tenant_id' => $wo->tenant_id,
            'center_id' => $wo->center_id,
            'customer_id' => $wo->customer_id,
            'work_order_id' => $useNullWorkOrderId ? null : ($workOrderIdOverride ?? $wo->id),
            'original_invoice_id' => $originalInvoiceId,
            'invoice_number' => $number,
            'type' => $type,
            'status' => $status,
            'issue_date' => now()->toDateString(),
            'supply_date' => now()->toDateString(),
            'total_excl_tax' => round($amount / 1.15, 2),
            'total_tax' => round($amount - ($amount / 1.15), 2),
            'total_incl_tax' => $amount,
            'total_paid' => 0,
            'payment_status' => 'unpaid',
        ]);
    }

    private function addPayment(WorkOrder $wo, ?Invoice $invoice, float $amount, PaymentType $type = PaymentType::PAYMENT): void
    {
        Payment::create([
            'tenant_id' => $wo->tenant_id,
            'center_id' => $wo->center_id,
            'work_order_id' => $wo->id,
            'invoice_id' => $invoice?->id,
            'amount' => $amount,
            'payment_date' => now(),
            'payment_method' => 'cash',
            'type' => $type,
            'received_by' => $this->adminUser->id,
        ]);
    }

    public function test_s1_to_s6_customer_and_dealer_rows_groups_and_kpis_reconcile(): void
    {
        // S1: 1000 unpaid -> remaining 1000
        $wo1 = $this->createDoneWorkOrder(1000.00, $this->centerA, $this->dealer);
        $this->createInvoice($wo1, 'INV-S1');

        // S2: 1000 + payment 200 -> remaining 800
        $wo2 = $this->createDoneWorkOrder(1000.00, $this->centerA, $this->dealer);
        $inv2 = $this->createInvoice($wo2, 'INV-S2');
        $this->addPayment($wo2, $inv2, 200.00);

        // S3: 1000 + valid CN 300 -> remaining 700
        $wo3 = $this->createDoneWorkOrder(1000.00, $this->centerA, $this->dealer);
        $inv3 = $this->createInvoice($wo3, 'INV-S3');
        $this->createInvoice($wo3, 'CN-S3', InvoiceType::CREDIT_NOTE->value, 'valid', 300.00, $inv3->id);

        // S4: 1000 + payment 200 + valid CN 300 -> remaining 500
        $wo4 = $this->createDoneWorkOrder(1000.00, $this->centerA, $this->dealer);
        $inv4 = $this->createInvoice($wo4, 'INV-S4');
        $this->addPayment($wo4, $inv4, 200.00);
        $this->createInvoice($wo4, 'CN-S4', InvoiceType::CREDIT_NOTE->value, 'valid', 300.00, $inv4->id);

        // S5: 1000 + full valid CN 1000 -> remaining 0
        $wo5 = $this->createDoneWorkOrder(1000.00, $this->centerA, $this->dealer);
        $inv5 = $this->createInvoice($wo5, 'INV-S5');
        $this->createInvoice($wo5, 'CN-S5', InvoiceType::CREDIT_NOTE->value, 'valid', 1000.00, $inv5->id);

        // S6: 1000 + draft, cancelled, soft-deleted CNs -> remaining still 1000
        $wo6 = $this->createDoneWorkOrder(1000.00, $this->centerA, $this->dealer);
        $inv6 = $this->createInvoice($wo6, 'INV-S6');
        $this->createInvoice($wo6, 'CN-S6-DRAFT', InvoiceType::CREDIT_NOTE->value, 'draft', 400.00, $inv6->id);
        $this->createInvoice($wo6, 'CN-S6-CANC', InvoiceType::CREDIT_NOTE->value, 'cancelled', 400.00, $inv6->id);
        $softDeletedCn = $this->createInvoice($wo6, 'CN-S6-DEL', InvoiceType::CREDIT_NOTE->value, 'valid', 400.00, $inv6->id);
        DB::table('invoices')->where('id', $softDeletedCn->id)->update(['deleted_at' => now()]);

        // Total expected remaining = 1000 + 800 + 700 + 500 + 0 + 1000 = 4000
        $expectedByCode = [
            $wo1->code => 1000.00,
            $wo2->code => 800.00,
            $wo3->code => 700.00,
            $wo4->code => 500.00,
            $wo5->code => 0.00,
            $wo6->code => 1000.00,
        ];

        // 1. Customers Report: KPI + Rows
        $this->get('/app/reports/customers?range=today&center_id='.$this->centerA->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('kpis.total_payment_remaining', fn ($v) => abs((float) $v - 4000.0) < 0.01)
                ->where('records.data', function ($rows) use ($expectedByCode) {
                    $items = collect($rows);

                    foreach ($expectedByCode as $code => $expected) {
                        $row = $items->firstWhere('code', $code);

                        if (! $row || abs((float) $row['payment_remaining'] - $expected) >= 0.01) {
                            return false;
                        }
                    }

                    return abs((float) $items->sum('payment_remaining') - 4000.0) < 0.01;
                })
            );

        // 2. Customers Report Grouped
        $this->get('/app/reports/customers?range=today&center_id='.$this->centerA->id.'&group_by=customer')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('groupedRecords.0.total_payment_remaining', fn ($v) => abs((float) $v - 4000.0) < 0.01)
            );

        // 3. Dealers Report: KPI + Rows
        $this->get('/app/reports/dealers?range=today&center_id='.$this->centerA->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('kpis.total_payment_remaining', fn ($v) => abs((float) $v - 4000.0) < 0.01)
                ->where('records.data', function ($rows) use ($expectedByCode) {
                    $items = collect($rows);

                    foreach ($expectedByCode as $code => $expected) {
                        $row = $items->firstWhere('code', $code);

                        if (! $row || abs((float) $row['payment_remaining'] - $expected) >= 0.01) {
                            return false;
                        }
                    }

                    return abs((float) $items->sum('payment_remaining') - 4000.0) < 0.01;
                })
            );

        // 4. Dealers Report Grouped
        $this->get('/app/reports/dealers?range=today&center_id='.$this->centerA->id.'&group_by=dealer')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('groupedRecords.0.total_payment_remaining', fn ($v) => abs((float) $v - 4000.0) < 0.01)
            );
    }

    public function test_s7_s8_s9_null_work_order_credit_note_fallback_without_double_counting(): void
    {
        // S7: direct work_order_id = wo7->id (1000 - 250 = 750)
        $wo7 = $this->createDoneWorkOrder(1000.00, $this->centerA, $this->dealer);
        $inv7 = $this->createInvoice($wo7, 'INV-S7');
        $this->createInvoice($wo7, 'CN-S7', InvoiceType::CREDIT_NOTE->value, 'valid', 250.00, null);

        // S8: work_order_id = null, original_invoice_id = inv8->id (1000 - 300 = 700)
        $wo8 = $this->createDoneWorkOrder(1000.00, $this->centerA, $this->dealer);
        $inv8 = $this->createInvoice($wo8, 'INV-S8');
        $this->createInvoice($wo8, 'CN-S8', InvoiceType::CREDIT_NOTE->value, 'valid', 300.00, $inv8->id, null, true);

        // S9: both work_order_id = wo9->id AND original_invoice_id = inv9->id (1000 - 400 = 600, counted ONCE)
        $wo9 = $this->createDoneWorkOrder(1000.00, $this->centerA, $this->dealer);
        $inv9 = $this->createInvoice($wo9, 'INV-S9');
        $this->createInvoice($wo9, 'CN-S9', InvoiceType::CREDIT_NOTE->value, 'valid', 400.00, $inv9->id);

        // Expected total = 750 + 700 + 600 = 2050
        $this->get('/app/reports/customers?range=today&center_id='.$this->centerA->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('kpis.total_payment_remaining', fn ($v) => abs((float) $v - 2050.0) < 0.01)
                ->where('records.data', function ($rows) use ($wo7, $wo8, $wo9) {
                    $items = collect($rows);

                    return abs((float) ($items->firstWhere('code', $wo7->code)['payment_remaining'] ?? -1) - 750.0) < 0.01
                        && abs((float) ($items->firstWhere('code', $wo8->code)['payment_remaining'] ?? -1) - 700.0) < 0.01
                        && abs((float) ($items->firstWhere('code', $wo9->code)['payment_remaining'] ?? -1) - 600.0) < 0.01;
                })
            );

        $this->get('/app/reports/dealers?range=today&center_id='.$this->centerA->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('kpis.total_payment_remaining', fn ($v) => abs((float) $v - 2050.0) < 0.01)
                ->where('records.data', function ($rows) use ($wo7, $wo8, $wo9) {
                    $items = collect($rows);

                    return abs((float) ($items->firstWhere('code', $wo7->code)['payment_remaining'] ?? -1) - 750.0) < 0.01
                        && abs((float) ($items->firstWhere('code', $wo8->code)['payment_remaining'] ?? -1) - 700.0) < 0.01
                        && abs((float) ($items->firstWhere('code', $wo9->code)['payment_remaining'] ?? -1) - 600.0) < 0.01;
                })
            );

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('checkedOutPaymentDue.count', 3)
                ->where('checkedOutPaymentDue.amount', fn ($v) => abs((float) $v - 2050.0) < 0.01)
            );
    }

    public function test_s10_s11_s12_refund_bad_debt_per_row_cap_branch_isolation_and_payment_status_filters(): void
    {
        // S10: WO1 1000: payment 600, refund 100 (net cash 500), bad_debt 200, valid CN 300 -> remaining 0 (paid)
        $woPaidByCn = $this->createDoneWorkOrder(1000.00, $this->centerA, $this->dealer);
        $invPaidByCn = $this->createInvoice($woPaidByCn, 'INV-S10-A');
        $this->addPayment($woPaidByCn, $invPaidByCn, 600.00, PaymentType::PAYMENT);
        $this->addPayment($woPaidByCn, $invPaidByCn, 100.00, PaymentType::REFUND);
        $this->addPayment($woPaidByCn, $invPaidByCn, 200.00, PaymentType::BAD_DEBT);
        $this->createInvoice($woPaidByCn, 'CN-S10-A', InvoiceType::CREDIT_NOTE->value, 'valid', 300.00, $invPaidByCn->id);

        // Overcredited WO2 (1000, CN 1200 -> remaining 0, not -200!) so it must NOT reduce WO3 (500 unpaid)
        $woOver = $this->createDoneWorkOrder(1000.00, $this->centerA, $this->dealer);
        $invOver = $this->createInvoice($woOver, 'INV-S10-OVER');
        $this->createInvoice($woOver, 'CN-S10-OVER', InvoiceType::CREDIT_NOTE->value, 'valid', 1200.00, $invOver->id);

        // WO3: partial via CN only (500, CN 150, paid 0 -> remaining 350, status partial)
        $woPartial = $this->createDoneWorkOrder(500.00, $this->centerA, $this->dealer);
        $invPartial = $this->createInvoice($woPartial, 'INV-S12-PART');
        $this->createInvoice($woPartial, 'CN-S12-PART', InvoiceType::CREDIT_NOTE->value, 'valid', 150.00, $invPartial->id);

        // WO4: unpaid (400 -> remaining 400, status unpaid)
        $woUnpaid = $this->createDoneWorkOrder(400.00, $this->centerA, $this->dealer);
        $this->createInvoice($woUnpaid, 'INV-S12-UNPAID');

        // S11: Center B WO (900) and Tenant B WO (5000)
        $woCenterB = $this->createDoneWorkOrder(900.00, $this->centerB, $this->dealer);
        $this->createInvoice($woCenterB, 'INV-S11-B');

        $otherTenant = Tenant::factory()->create(['status' => 'active']);
        $otherCenter = Center::factory()->create(['tenant_id' => $otherTenant->id, 'is_active' => true]);
        $otherCustomer = Customer::factory()->create(['tenant_id' => $otherTenant->id, 'center_id' => $otherCenter->id]);
        $otherVehicle = Vehicle::factory()->create([
            'tenant_id' => $otherTenant->id,
            'center_id' => $otherCenter->id,
            'customer_id' => $otherCustomer->id,
        ]);
        $otherWo = new WorkOrder;
        $otherWo->forceFill([
            'tenant_id' => $otherTenant->id,
            'center_id' => $otherCenter->id,
            'customer_id' => $otherCustomer->id,
            'vehicle_id' => $otherVehicle->id,
            'code' => 'WO-OTHER-T',
            'status' => 'done',
            'entry_date' => now()->toDateString(),
            'total_incl_tax' => 5000.00,
        ]);
        $otherWo->saveQuietly();

        // Center A total remaining = 0 + 0 + 350 + 400 = 750 (not 550!)
        $this->get('/app/reports/customers?range=today&center_id='.$this->centerA->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('kpis.total_payment_remaining', fn ($v) => abs((float) $v - 750.0) < 0.01)
            );

        // S12: payment_status=paid returns woPaidByCn and woOver (2 rows)
        $this->get('/app/reports/customers?range=today&center_id='.$this->centerA->id.'&payment_status=paid')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('kpis.total_records', 2)
                ->where('kpis.total_payment_remaining', fn ($v) => (float) $v === 0.0)
            );

        // S12: payment_status=partial returns woPartial (1 row, 350 remaining)
        $this->get('/app/reports/customers?range=today&center_id='.$this->centerA->id.'&payment_status=partial')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('kpis.total_records', 1)
                ->where('kpis.total_payment_remaining', fn ($v) => abs((float) $v - 350.0) < 0.01)
            );

        // S12: payment_status=unpaid returns woUnpaid (1 row, 400 remaining)
        $this->get('/app/reports/customers?range=today&center_id='.$this->centerA->id.'&payment_status=unpaid')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('kpis.total_records', 1)
                ->where('kpis.total_payment_remaining', fn ($v) => abs((float) $v - 400.0) < 0.01)
            );

        // S11: single-branch user cannot see Center B
        $this->actingAs($this->singleBranchUser);
        $this->get('/app/reports/customers?range=today&center_id='.$this->centerB->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('kpis.total_records', 0)
                ->where('kpis.total_payment_remaining', fn ($v) => (float) $v === 0.0)
            );
    }
}
