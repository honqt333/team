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
use App\Models\Role;
use App\Models\Tenant;
use App\Models\TenantWebsite;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Services\CreditNoteService;
use App\Services\InvoiceService;
use App\Support\CustomerPortalSession;
use App\Support\Permissions;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CustomerDealerStatementAndPortalConsistencyTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Center $center;

    private Center $centerB;

    private User $user;

    private function setUpTenant(): void
    {
        $this->tenant = Tenant::factory()->create(['status' => 'active']);
        TenantWebsite::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'published',
        ]);

        $this->center = Center::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'الفرع الرئيسي',
            'is_active' => true,
        ]);

        $this->centerB = Center::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'الفرع الثاني',
            'is_active' => true,
        ]);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        Permission::findOrCreate(Permissions::CUSTOMERS_VIEW, 'web');

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
            'is_active' => true,
        ]);
        setPermissionsTeamId($this->tenant->id);
        $this->user->givePermissionTo(Permissions::CUSTOMERS_VIEW);
        $this->user->centers()->attach([
            $this->center->id => ['tenant_id' => $this->tenant->id],
            $this->centerB->id => ['tenant_id' => $this->tenant->id],
        ]);
        $this->actingAs($this->user);
    }

    private function loginPortal(Customer $customer, Tenant $tenant): void
    {
        session()->put(CustomerPortalSession::key(), [
            'customer_id' => $customer->id,
            'tenant_id' => $tenant->id,
            'last_seen_at' => Carbon::now()->toIso8601String(),
        ]);
    }

    private function createWorkOrderWithService(
        Customer $customer,
        float $price,
        ?Dealer $dealer = null,
        ?Center $center = null,
    ): WorkOrder {
        $targetCenter = $center ?? $this->center;
        $vehicle = Vehicle::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $targetCenter->id,
            'customer_id' => $customer->id,
            'dealer_id' => $dealer?->id,
        ]);

        $wo = WorkOrder::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $targetCenter->id,
            'customer_id' => $customer->id,
            'dealer_id' => $dealer?->id,
            'vehicle_id' => $vehicle->id,
            'status' => 'open',
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
            'title' => 'صيانة شاملة',
            'unit_price' => $price,
            'qty' => 1,
            'discount_amount' => 0,
            'line_total_excl_tax' => $price,
            'line_total_incl_tax' => $price,
            'status' => 'pending',
        ]);

        return $wo->fresh();
    }

    private function issueInvoiceForWorkOrder(WorkOrder $wo, string $number = 'INV-1001'): Invoice
    {
        $invoice = app(InvoiceService::class)->createFromWorkOrder($wo, $this->user);
        $invoice->forceFill([
            'status' => 'valid',
            'invoice_number' => $number,
            'issue_date' => now()->subDays(2)->toDateString(),
        ])->saveQuietly();
        $invoice->updatePaymentStatus();

        return $invoice->fresh();
    }

    private function createCreditNoteForWorkOrder(
        WorkOrder $wo,
        float $amount,
        bool $recordRefund = false,
    ): Invoice {
        $item = $wo->items()->firstOrFail();

        return app(CreditNoteService::class)->createCreditNote($wo, [
            'services' => [[
                'work_order_item_id' => $item->id,
                'return_amount' => $amount,
            ]],
            'parts' => [],
            'reason' => 'خصم / إرجاع خدمة',
            'record_refund' => $recordRefund,
            'refund_payment_method' => 'cash',
        ], $this->user);
    }

    public function test_s1_s2_s3_s4_customer_dealer_and_portal_statements_match_financial_contract(): void
    {
        $this->setUpTenant();

        $customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
        ]);
        $dealer = Dealer::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'تاجر تجربة S1',
            'type' => 'showroom',
            'is_active' => true,
        ]);

        // S1: Unpaid invoice 1000
        $wo = $this->createWorkOrderWithService($customer, 1000.00, $dealer);
        $inv = $this->issueInvoiceForWorkOrder($wo, 'INV-S1');

        $this->get(route('customers.show', $customer))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('financialSummary.total_invoiced', 1000)
                ->where('financialSummary.total_paid', 0)
                ->where('financialSummary.total_due', 1000)
                ->has('ledger', 1)
                ->where('ledger.0.type', 'invoice')
                ->where('ledger.0.debit', 1000)
                ->where('ledger.0.balance', 1000)
            );

        $this->get(route('app.dealers.show', $dealer))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('financialSummary.total_invoiced', 1000)
                ->where('financialSummary.total_due', 1000)
                ->where('outstandingBalance', 1000)
                ->has('ledger', 1)
                ->where('ledger.0.balance', 1000)
            );

        $this->loginPortal($customer, $this->tenant);
        $this->get('/'.$this->tenant->slug.'/portal/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('summary.unpaid_balance', 1000)
                ->where('summary.unpaid_invoices_count', 1)
            );

        // S2: Partial payment 200 -> 800
        Payment::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'work_order_id' => $wo->id,
            'invoice_id' => $inv->id,
            'amount' => 200.00,
            'payment_date' => now()->subDay(),
            'payment_method' => 'cash',
            'type' => PaymentType::PAYMENT,
            'received_by' => $this->user->id,
        ]);
        $inv->updatePaymentStatus();

        $this->get(route('customers.show', $customer))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('financialSummary.total_invoiced', 1000)
                ->where('financialSummary.total_paid', 200)
                ->where('financialSummary.total_due', 800)
                ->has('ledger', 2)
                ->where('ledger.1.type', 'payment')
                ->where('ledger.1.credit', 200)
                ->where('ledger.1.balance', 800)
            );

        // S4 (and S3 credit-note debt offset): Valid CN 300 -> remaining 500
        $cn = $this->createCreditNoteForWorkOrder($wo, 300.00, false);
        $this->assertSame(0.0, (float) $cn->fresh()->net_refund_due);

        $this->get(route('customers.show', $customer))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('financialSummary.total_invoiced', 1000)
                ->where('financialSummary.total_credit_notes', 300)
                ->where('financialSummary.total_paid', 200)
                ->where('financialSummary.total_due', 500)
                ->where('financialSummary.total_refund_due', 0)
                ->has('ledger', 3)
                ->where('ledger.2.type', 'credit_note')
                ->where('ledger.2.debit', 0)
                ->where('ledger.2.credit', 300)
                ->where('ledger.2.balance', 500)
            );

        $this->get(route('customers.statement.print', $customer))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('financialSummary.total_due', 500)
                ->where('ledger.2.type', 'credit_note')
                ->where('ledger.2.balance', 500)
            );

        $this->get(route('app.dealers.show', $dealer))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('financialSummary.total_invoiced', 1000)
                ->where('financialSummary.total_credit_notes', 300)
                ->where('financialSummary.total_paid', 200)
                ->where('financialSummary.total_due', 500)
                ->where('outstandingBalance', 500)
                ->has('ledger', 3)
                ->where('ledger.2.type', 'credit_note')
                ->where('ledger.2.credit', 300)
                ->where('ledger.2.balance', 500)
            );

        $this->get('/'.$this->tenant->slug.'/portal/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('summary.unpaid_balance', 500)
                ->where('summary.unpaid_invoices_count', 1)
            );

        $this->get('/'.$this->tenant->slug.'/portal/invoices')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('financialSummary.total_invoiced', 1000)
                ->where('financialSummary.total_balance', 500)
                ->where('financialSummary.unpaid_count', 1)
            );
    }

    public function test_s5_s6_s9_paid_invoice_credit_note_refund_due_and_refund_payment(): void
    {
        $this->setUpTenant();

        $customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
        ]);
        $dealer = Dealer::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'تاجر تجربة S1',
            'type' => 'showroom',
            'is_active' => true,
        ]);

        // S6: Invoice 1000, paid 800, CN 500 (no immediate refund)
        // debt_offset = 200, net_refund_due = 300
        $wo = $this->createWorkOrderWithService($customer, 1000.00, $dealer);
        $inv = $this->issueInvoiceForWorkOrder($wo, 'INV-S6');

        Payment::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'work_order_id' => $wo->id,
            'invoice_id' => $inv->id,
            'amount' => 800.00,
            'payment_date' => now()->subDay(),
            'payment_method' => 'cash',
            'type' => PaymentType::PAYMENT,
            'received_by' => $this->user->id,
        ]);
        $inv->updatePaymentStatus();

        $cn = $this->createCreditNoteForWorkOrder($wo, 500.00, false);
        $this->assertSame(200.0, (float) $cn->debt_offset);
        $this->assertSame(300.0, (float) $cn->net_refund_due);

        // Customer statement: total_due = 0 (not 300!), total_refund_due = 300, ledger running = -300
        $this->get(route('customers.show', $customer))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('financialSummary.total_invoiced', 1000)
                ->where('financialSummary.total_credit_notes', 500)
                ->where('financialSummary.total_paid', 800)
                ->where('financialSummary.total_due', 0)
                ->where('financialSummary.total_refund_due', 300)
                ->has('ledger', 3)
                ->where('ledger.2.type', 'credit_note')
                ->where('ledger.2.credit', 500)
                ->where('ledger.2.balance', -300)
            );

        // Dealer statement: total_due = 0, total_refund_due = 300, ledger running = -300
        $this->get(route('app.dealers.show', $dealer))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('financialSummary.total_due', 0)
                ->where('financialSummary.total_refund_due', 300)
                ->where('outstandingBalance', 0)
                ->where('ledger.2.type', 'credit_note')
                ->where('ledger.2.balance', -300)
            );

        // Portal must NOT show the 300 credit note refund_due as unpaid balance owed by customer
        $this->loginPortal($customer, $this->tenant);
        $this->get('/'.$this->tenant->slug.'/portal/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('summary.unpaid_balance', 0)
                ->where('summary.unpaid_invoices_count', 0)
            );
        $this->get('/'.$this->tenant->slug.'/portal/invoices')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('financialSummary.total_invoiced', 1000)
                ->where('financialSummary.total_paid', 800)
                ->where('financialSummary.total_balance', 0)
                ->where('financialSummary.unpaid_count', 0)
            );

        // Portal work order show: linked_invoice must point to original invoice, not the credit note
        $this->get('/'.$this->tenant->slug.'/portal/work-order/'.$wo->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('workOrder.linked_invoice.id', $inv->id)
                ->where('workOrder.linked_invoice.balance', 0)
            );

        // S9: Record Refund of 300 against the Credit Note
        Payment::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'work_order_id' => $wo->id,
            'invoice_id' => $cn->id,
            'amount' => 300.00,
            'payment_date' => now()->addMinutes(2),
            'payment_method' => 'cash',
            'type' => PaymentType::REFUND,
            'received_by' => $this->user->id,
        ]);
        $cn->fresh()->forceFill(['total_paid' => 500.00, 'payment_status' => 'paid'])->saveQuietly();

        // Running balance returns from -300 to 0; total_paid = 800 - 300 = 500; total_refund_due = 0
        $this->get(route('customers.show', $customer))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('financialSummary.total_paid', 500)
                ->where('financialSummary.total_refunded', 300)
                ->where('financialSummary.total_due', 0)
                ->where('financialSummary.total_refund_due', 0)
                ->has('ledger', 4)
                ->where('ledger.3.type', 'refund')
                ->where('ledger.3.debit', 300)
                ->where('ledger.3.credit', 0)
                ->where('ledger.3.balance', 0)
            );
    }

    public function test_s7_s8_s10_draft_cancelled_soft_deleted_and_bad_debt_handling(): void
    {
        $this->setUpTenant();

        $customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
        ]);

        $wo = $this->createWorkOrderWithService($customer, 1000.00);
        $inv = $this->issueInvoiceForWorkOrder($wo, 'INV-S10');

        // Draft credit note & cancelled credit note & soft-deleted credit note must be ignored
        Invoice::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'customer_id' => $customer->id,
            'original_invoice_id' => $inv->id,
            'type' => InvoiceType::CREDIT_NOTE,
            'status' => 'draft',
            'total_excl_tax' => 200,
            'total_incl_tax' => 200,
        ]);
        Invoice::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'customer_id' => $customer->id,
            'original_invoice_id' => $inv->id,
            'type' => InvoiceType::CREDIT_NOTE,
            'status' => 'cancelled',
            'total_excl_tax' => 200,
            'total_incl_tax' => 200,
        ]);
        $softDeletedCn = Invoice::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'customer_id' => $customer->id,
            'original_invoice_id' => $inv->id,
            'type' => InvoiceType::CREDIT_NOTE,
            'status' => 'valid',
            'total_excl_tax' => 200,
            'total_incl_tax' => 200,
        ]);
        DB::table('invoices')->where('id', $softDeletedCn->id)->update(['deleted_at' => now()]);

        // Draft original invoice must also be excluded from statement ledger & summary
        Invoice::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'customer_id' => $customer->id,
            'type' => InvoiceType::INVOICE,
            'status' => 'draft',
            'total_excl_tax' => 500,
            'total_incl_tax' => 500,
        ]);

        // S10: Bad Debt write-off of 1000 (not cash collected)
        Payment::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'work_order_id' => $wo->id,
            'invoice_id' => $inv->id,
            'amount' => 1000.00,
            'payment_date' => now()->addMinute(),
            'payment_method' => 'cash',
            'type' => PaymentType::BAD_DEBT,
            'received_by' => $this->user->id,
        ]);
        $inv->updatePaymentStatus();

        $this->get(route('customers.show', $customer))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('financialSummary.total_invoiced', 1000)
                ->where('financialSummary.total_paid', 0)
                ->where('financialSummary.total_bad_debt', 1000)
                ->where('financialSummary.total_due', 0)
                ->has('ledger', 2)
                ->where('ledger.1.type', 'bad_debt')
                ->where('ledger.1.debit', 0)
                ->where('ledger.1.credit', 1000)
                ->where('ledger.1.balance', 0)
            );
    }

    public function test_s11_s12_multi_center_cross_tenant_and_authorization(): void
    {
        $this->setUpTenant();

        $customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
        ]);

        // Center A: 1000 invoice - 300 CN = 700
        $woA = $this->createWorkOrderWithService($customer, 1000.00, null, $this->center);
        $this->issueInvoiceForWorkOrder($woA, 'INV-A');
        $this->createCreditNoteForWorkOrder($woA, 300.00, false);

        // Center B: 500 invoice
        $this->user->forceFill(['current_center_id' => $this->centerB->id])->save();
        $woB = $this->createWorkOrderWithService($customer, 500.00, null, $this->centerB);
        $this->issueInvoiceForWorkOrder($woB, 'INV-B');
        $this->user->forceFill(['current_center_id' => $this->center->id])->save();

        // Other Tenant: 9999 invoice (must never leak)
        $otherTenant = Tenant::factory()->create(['status' => 'active']);
        $otherCenter = Center::factory()->create(['tenant_id' => $otherTenant->id]);
        $otherCustomer = Customer::factory()->create([
            'tenant_id' => $otherTenant->id,
            'center_id' => $otherCenter->id,
        ]);
        Invoice::factory()->create([
            'tenant_id' => $otherTenant->id,
            'center_id' => $otherCenter->id,
            'customer_id' => $otherCustomer->id,
            'type' => InvoiceType::INVOICE,
            'status' => 'valid',
            'total_excl_tax' => 9999,
            'total_incl_tax' => 9999,
        ]);

        // Customer statement (tenant-wide for this customer): 700 + 500 = 1200
        $this->get(route('customers.show', $customer))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('financialSummary.total_invoiced', 1500)
                ->where('financialSummary.total_credit_notes', 300)
                ->where('financialSummary.total_due', 1200)
            );

        // Portal (tenant-wide for this customer across centers): 1200
        $this->loginPortal($customer, $this->tenant);
        $this->get('/'.$this->tenant->slug.'/portal/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('summary.unpaid_balance', 1200)
                ->where('summary.unpaid_invoices_count', 2)
            );

        // Empty customer (no invoices) -> 0s
        $emptyCustomer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
        ]);
        $this->get(route('customers.show', $emptyCustomer))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('financialSummary.total_invoiced', 0)
                ->where('financialSummary.total_paid', 0)
                ->where('financialSummary.total_due', 0)
                ->has('ledger', 0)
            );

        // Unauthorized user cannot view customer or dealer statement
        $noPermRole = Role::create([
            'name' => 'viewer_none',
            'display_name' => 'No Perm',
            'permissions' => [],
        ]);
        $noPermUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
            'is_active' => true,
        ]);
        $noPermUser->roles()->attach($noPermRole->id, ['tenant_id' => $this->tenant->id]);
        $noPermUser->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);
        $this->actingAs($noPermUser);

        $this->get(route('customers.show', $customer))->assertForbidden();
        $this->get(route('customers.statement.print', $customer))->assertForbidden();
    }
}
