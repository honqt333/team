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
use App\Models\WorkOrderItem;
use App\Services\PaymentService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CustomerAndDealerOutstandingBalanceTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenantA;

    private Tenant $tenantB;

    private Center $centerA1;

    private Center $centerA2;

    private Center $centerB1;

    private User $userA;

    private Customer $customerA;

    private Dealer $dealerA;

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

        $this->customerA = Customer::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA1->id,
            'credit_limit' => 1000.00,
        ]);

        $this->dealerA = Dealer::create([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA1->id,
            'name' => 'Dealer A',
            'type' => 'showroom',
            'phone' => '0500000001',
            'is_active' => true,
        ]);

        $this->actingAs($this->userA);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function createInvoice(array $overrides = []): Invoice
    {
        return Invoice::factory()->create(array_merge([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA1->id,
            'customer_id' => $this->customerA->id,
            'type' => InvoiceType::INVOICE,
            'status' => 'issued',
            'payment_status' => 'unpaid',
            'total_excl_tax' => 1000.00,
            'total_tax' => 0.00,
            'total_incl_tax' => 1000.00,
            'total_paid' => 0.00,
            'issue_date' => now(),
        ], $overrides));
    }

    private function createCreditNote(Invoice $original, float $amount, array $overrides = []): Invoice
    {
        return Invoice::factory()->create(array_merge([
            'tenant_id' => $original->tenant_id,
            'center_id' => $original->center_id,
            'customer_id' => $original->customer_id,
            'dealer_id' => $original->dealer_id,
            'original_invoice_id' => $original->id,
            'type' => InvoiceType::CREDIT_NOTE,
            'status' => 'valid',
            'payment_status' => 'paid',
            'total_excl_tax' => $amount,
            'total_tax' => 0.00,
            'total_incl_tax' => $amount,
            'total_paid' => $amount,
            'issue_date' => now(),
        ], $overrides));
    }

    public function test_s1_to_s7_customer_and_dealer_outstanding_match_invoice_balance(): void
    {
        // S1: Unpaid invoice 1000 -> 1000
        $inv1 = $this->createInvoice(['dealer_id' => $this->dealerA->id]);
        $this->assertEqualsWithDelta(1000.00, $inv1->balance, 0.01);
        $this->assertEqualsWithDelta(1000.00, $this->customerA->getOutstandingBalance(), 0.01);
        $this->assertEqualsWithDelta(1000.00, $this->dealerA->getOutstandingBalance(), 0.01);

        // S2: Partial payment 200 -> 800
        app(PaymentService::class)->recordPayment($inv1, ['amount' => 200.00, 'payment_method' => 'cash']);
        $inv1->refresh();
        $this->assertEqualsWithDelta(800.00, $inv1->balance, 0.01);
        $this->assertEqualsWithDelta(800.00, $this->customerA->getOutstandingBalance(), 0.01);
        $this->assertEqualsWithDelta(800.00, $this->dealerA->getOutstandingBalance(), 0.01);

        // S3 & S4: Valid credit note 300 with existing 200 payment -> 500
        $cn1 = $this->createCreditNote($inv1, 300.00);
        $inv1->updatePaymentStatus();
        $inv1->refresh();
        $this->assertEqualsWithDelta(500.00, $inv1->balance, 0.01);
        $this->assertEqualsWithDelta(500.00, $this->customerA->getOutstandingBalance(), 0.01);
        $this->assertEqualsWithDelta(500.00, $this->dealerA->getOutstandingBalance(), 0.01);

        // S6: Cancelled credit note 300 must be ignored -> still 500
        $this->createCreditNote($inv1, 300.00, ['status' => 'cancelled']);
        $inv1->updatePaymentStatus();
        $this->assertEqualsWithDelta(500.00, $inv1->fresh()->balance, 0.01);
        $this->assertEqualsWithDelta(500.00, $this->customerA->getOutstandingBalance(), 0.01);
        $this->assertEqualsWithDelta(500.00, $this->dealerA->getOutstandingBalance(), 0.01);

        // S7: Soft-deleted credit note 300 must be ignored -> still 500
        $deletedCn = $this->createCreditNote($inv1, 300.00);
        $deletedCn->forceFill(['status' => 'draft'])->saveQuietly();
        $deletedCn->delete();
        $deletedCn->forceFill(['status' => 'valid'])->saveQuietly();
        $this->assertEqualsWithDelta(500.00, $inv1->fresh()->balance, 0.01);
        $this->assertEqualsWithDelta(500.00, $this->customerA->getOutstandingBalance(), 0.01);
        $this->assertEqualsWithDelta(500.00, $this->dealerA->getOutstandingBalance(), 0.01);

        // S5: Full credit note on unpaid invoice -> 0 (plus inv1 still 500)
        $inv2 = $this->createInvoice(['dealer_id' => $this->dealerA->id]);
        $this->createCreditNote($inv2, 1000.00);
        $inv2->updatePaymentStatus();
        $this->assertEqualsWithDelta(0.00, $inv2->fresh()->balance, 0.01);
        $this->assertEqualsWithDelta(500.00, $this->customerA->getOutstandingBalance(), 0.01);
        $this->assertEqualsWithDelta(500.00, $this->dealerA->getOutstandingBalance(), 0.01);

        // S3 pure (unpaid 1000 + valid CN 300, no payments) on a fresh customer
        $customerPure = Customer::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA1->id,
            'credit_limit' => 800.00,
        ]);
        $invPure = $this->createInvoice(['customer_id' => $customerPure->id]);
        $this->createCreditNote($invPure, 300.00);
        $invPure->updatePaymentStatus();
        $this->assertEqualsWithDelta(700.00, $customerPure->getOutstandingBalance(), 0.01);
        // Credit limit 800: 700 + 50 = 750 < 800 (not exceeded; previously 1000 would falsely exceed!)
        $this->assertFalse($customerPure->isCreditLimitExceeded(50.00));
    }

    public function test_s8_draft_and_s9_mixed_settlement_do_not_inflate_customer_or_dealer_balance(): void
    {
        // S8: Draft invoice 1000 -> contribution 0
        $this->createInvoice(['dealer_id' => $this->dealerA->id, 'status' => 'draft']);
        $this->assertEqualsWithDelta(0.00, $this->customerA->getOutstandingBalance(), 0.01);
        $this->assertEqualsWithDelta(0.00, $this->dealerA->getOutstandingBalance(), 0.01);

        // S9: Invoice 1000, paid 600, valid credit note 500 (unpaid 100 excess credit due to customer)
        $inv = $this->createInvoice(['dealer_id' => $this->dealerA->id]);
        app(PaymentService::class)->recordPayment($inv, ['amount' => 600.00, 'payment_method' => 'cash']);
        $this->createCreditNote($inv, 500.00, [
            'payment_status' => 'partial',
            'total_paid' => 400.00,
        ]);
        $inv->updatePaymentStatus();
        $inv->refresh();

        $this->assertEqualsWithDelta(0.00, $inv->balance, 0.01);
        // Excess credit (100) must NOT be added as positive debt nor double-subtracted
        $this->assertEqualsWithDelta(0.00, $this->customerA->getOutstandingBalance(), 0.01);
        $this->assertEqualsWithDelta(0.00, $this->dealerA->getOutstandingBalance(), 0.01);
    }

    public function test_s10_refund_and_s11_bad_debt_are_not_double_counted(): void
    {
        $inv = $this->createInvoice(['dealer_id' => $this->dealerA->id]);

        // Payment 700, then refund 200 on the original invoice -> net_paid = 500, balance = 500
        app(PaymentService::class)->recordPayment($inv, ['amount' => 700.00, 'payment_method' => 'cash']);
        Payment::create([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA1->id,
            'invoice_id' => $inv->id,
            'amount' => 200.00,
            'payment_date' => now(),
            'payment_method' => 'cash',
            'type' => PaymentType::REFUND,
            'received_by' => $this->userA->id,
        ]);
        // Bad debt write-off 100 -> net_paid = 600, balance = 400
        Payment::create([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA1->id,
            'invoice_id' => $inv->id,
            'amount' => 100.00,
            'payment_date' => now(),
            'payment_method' => 'cash',
            'type' => PaymentType::BAD_DEBT,
            'received_by' => $this->userA->id,
        ]);
        $inv->updatePaymentStatus();

        $this->assertEqualsWithDelta(400.00, $inv->fresh()->balance, 0.01);
        $this->assertEqualsWithDelta(400.00, $this->customerA->getOutstandingBalance(), 0.01);
        $this->assertEqualsWithDelta(400.00, $this->dealerA->getOutstandingBalance(), 0.01);
    }

    public function test_s12_tenant_and_center_isolation_and_controller_queries_match(): void
    {
        // Customer A & Dealer A have invoices in Center A1 (700 net) and Center A2 (200 net)
        $invA1 = $this->createInvoice(['center_id' => $this->centerA1->id, 'dealer_id' => $this->dealerA->id]);
        $this->createCreditNote($invA1, 300.00);
        $invA1->updatePaymentStatus();

        $invA2 = $this->createInvoice([
            'center_id' => $this->centerA2->id,
            'dealer_id' => $this->dealerA->id,
            'total_incl_tax' => 500.00,
        ]);
        $this->userA->forceFill(['current_center_id' => $this->centerA2->id])->saveQuietly();
        app(PaymentService::class)->recordPayment($invA2, ['amount' => 300.00, 'payment_method' => 'cash']);
        $this->userA->forceFill(['current_center_id' => $this->centerA1->id])->saveQuietly();

        // Uninvoiced work order for Dealer A (350 due)
        $wo = WorkOrder::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA1->id,
            'customer_id' => $this->customerA->id,
            'dealer_id' => $this->dealerA->id,
            'status' => 'open',
            'total_incl_tax' => 350.00,
        ]);
        WorkOrderItem::create([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA1->id,
            'work_order_id' => $wo->id,
            'title' => 'Test Service',
            'unit_price' => 350.00,
            'qty' => 1,
            'discount_amount' => 0.00,
            'line_total_excl_tax' => 350.00,
            'line_total_incl_tax' => 350.00,
            'status' => 'pending',
        ]);

        // Cross-tenant invoice for Tenant B must never leak
        $customerB = Customer::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'center_id' => $this->centerB1->id,
        ]);
        $this->createInvoice([
            'tenant_id' => $this->tenantB->id,
            'center_id' => $this->centerB1->id,
            'customer_id' => $customerB->id,
            'total_incl_tax' => 9999.00,
        ]);

        // Vehicle linked to Customer A
        $vehicle = Vehicle::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA1->id,
            'customer_id' => $this->customerA->id,
            'plate_number' => 'ABC-1234',
        ]);

        // Expected customer outstanding across A1+A2 = 700 + 200 = 900
        $this->assertEqualsWithDelta(900.00, $this->customerA->getOutstandingBalance(), 0.01);
        // Expected dealer outstanding across A1+A2 + uninvoiced WO = 900 + 350 = 1250
        $this->assertEqualsWithDelta(1250.00, $this->dealerA->getOutstandingBalance(), 0.01);

        // CustomerController::index (api) / buildCustomerQuery
        $custResp = $this->getJson('/app/customers?format=json');
        // Fallback: Inertia props on /app/customers
        $customersPage = $this->get('/app/customers');
        $customersPage->assertOk();
        $customersProp = collect($customersPage->viewData('page')['props']['customers']['data'] ?? []);
        $rowCust = $customersProp->firstWhere('id', $this->customerA->id);
        $this->assertNotNull($rowCust);
        $this->assertEqualsWithDelta(900.00, (float) $rowCust['balance'], 0.01);

        // DealerController::index
        $dealersPage = $this->get('/app/dealers');
        $dealersPage->assertOk();
        $dealersProp = collect($dealersPage->viewData('page')['props']['dealers']['data'] ?? []);
        $rowDealer = $dealersProp->firstWhere('id', $this->dealerA->id);
        $this->assertNotNull($rowDealer);
        $this->assertEqualsWithDelta(1250.00, (float) $rowDealer['outstanding_balance'], 0.01);

        // VehicleController::apiIndex
        $vehiclesResp = $this->getJson(route('vehicles.api.index'));
        $vehiclesResp->assertOk();
        $vehiclesProp = collect($vehiclesResp->json('data') ?? []);
        $rowVehicle = $vehiclesProp->firstWhere('id', $vehicle->id);
        $this->assertNotNull($rowVehicle);
        $this->assertEqualsWithDelta(900.00, (float) ($rowVehicle['customer_balance'] ?? $rowVehicle['customer']['balance'] ?? -1), 0.01);

        // Api\WorkOrderController::vehicleSearch
        $searchResp = $this->getJson('/app/api/vehicles/search?q=ABC-1234');
        $searchResp->assertOk();
        $searchData = collect($searchResp->json('vehicles') ?? $searchResp->json('data') ?? $searchResp->json());
        $foundVehicle = $searchData->firstWhere('id', $vehicle->id);
        $this->assertNotNull($foundVehicle);
        $this->assertEqualsWithDelta(900.00, (float) ($foundVehicle['customer_balance'] ?? $foundVehicle['customer']['balance'] ?? -1), 0.01);

        // DashboardController::index (Center A1 only -> invA1 = 700)
        $dashPage = $this->get('/dashboard?refresh=1');
        $dashPage->assertOk();
        $dashOutstanding = (float) ($dashPage->viewData('page')['props']['stats']['revenue']['outstanding'] ?? -1);
        $this->assertEqualsWithDelta(700.00, $dashOutstanding, 0.01);
    }
}
