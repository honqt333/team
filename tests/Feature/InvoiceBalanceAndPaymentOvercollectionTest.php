<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exceptions\PaymentExceedsBalanceException;
use App\Models\Center;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PaymentService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class InvoiceBalanceAndPaymentOvercollectionTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenantA;

    private Tenant $tenantB;

    private Center $centerA1;

    private Center $centerA2;

    private Center $centerB1;

    private User $userA;

    private Customer $customerA1;

    private PaymentService $paymentService;

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
        $this->paymentService = app(PaymentService::class);
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
            'status' => 'valid',
            'payment_status' => 'unpaid',
            'issue_date' => now(),
            'total_excl_tax' => $total,
            'total_tax' => 0.00,
            'total_incl_tax' => $total,
            'total_paid' => 0.00,
        ]);
    }

    private function createCreditNote(Invoice $originalInvoice, float $total, string $status = 'valid', ?int $tenantId = null, ?int $centerId = null): Invoice
    {
        return Invoice::factory()->create([
            'tenant_id' => $tenantId ?? $this->tenantA->id,
            'center_id' => $centerId ?? $this->centerA1->id,
            'customer_id' => $this->customerA1->id,
            'original_invoice_id' => $originalInvoice->id,
            'type' => 'credit_note',
            'status' => $status,
            'payment_status' => $status === 'valid' ? 'paid' : 'unpaid',
            'issue_date' => now(),
            'total_excl_tax' => $total,
            'total_tax' => 0.00,
            'total_incl_tax' => $total,
            'total_paid' => $status === 'valid' ? $total : 0.00,
        ]);
    }

    public function test_scenario_1_partial_credit_note_and_prior_payment_caps_new_payment_at_remaining_balance(): void
    {
        $invoice = $this->createIssuedInvoice(1000.00);
        $this->paymentService->recordPayment($invoice, ['amount' => 200.00, 'payment_method' => 'cash']);
        $this->createCreditNote($invoice, 300.00);
        $invoice->updatePaymentStatus();
        $invoice->refresh();

        $this->assertEquals(500.00, (float) $invoice->balance);

        // Overpayment of 501 must be rejected
        $overpaymentRejected = false;

        try {
            $this->paymentService->recordPayment($invoice, ['amount' => 501.00, 'payment_method' => 'cash']);
        } catch (PaymentExceedsBalanceException $e) {
            $overpaymentRejected = true;
        }
        $this->assertTrue($overpaymentRejected, 'Payment of 501 must be rejected when remaining balance is 500.');

        // Exact remaining payment of 500 must succeed
        $this->paymentService->recordPayment($invoice, ['amount' => 500.00, 'payment_method' => 'cash']);
        $invoice->refresh();

        $this->assertSame('paid', $invoice->payment_status);
        $this->assertEquals(700.00, (float) $invoice->total_paid);
        $this->assertEquals(0.00, (float) $invoice->balance);
    }

    public function test_scenario_2_full_credit_note_sets_balance_zero_and_blocks_payment(): void
    {
        $invoice = $this->createIssuedInvoice(1000.00);
        $this->createCreditNote($invoice, 1000.00);
        $invoice->updatePaymentStatus();
        $invoice->refresh();

        $this->assertSame('paid', $invoice->payment_status);
        $this->assertEquals(0.00, (float) $invoice->total_paid);
        $this->assertEquals(0.00, (float) $invoice->balance);

        $this->expectException(PaymentExceedsBalanceException::class);
        $this->paymentService->recordPayment($invoice, ['amount' => 1.00, 'payment_method' => 'cash']);
    }

    public function test_scenario_3_multiple_credit_notes_and_prior_payment_reduce_balance_accurately(): void
    {
        $invoice = $this->createIssuedInvoice(1000.00);
        $this->paymentService->recordPayment($invoice, ['amount' => 100.00, 'payment_method' => 'cash']);
        $this->createCreditNote($invoice, 200.00);
        $this->createCreditNote($invoice, 300.00);
        $invoice->updatePaymentStatus();
        $invoice->refresh();

        $this->assertEquals(400.00, (float) $invoice->balance);

        $rejected = false;

        try {
            $this->paymentService->recordPayment($invoice, ['amount' => 401.00, 'payment_method' => 'cash']);
        } catch (PaymentExceedsBalanceException $e) {
            $rejected = true;
        }
        $this->assertTrue($rejected);

        $this->paymentService->recordPayment($invoice, ['amount' => 400.00, 'payment_method' => 'cash']);
        $invoice->refresh();
        $this->assertSame('paid', $invoice->payment_status);
        $this->assertEquals(500.00, (float) $invoice->total_paid);
        $this->assertEquals(0.00, (float) $invoice->balance);
    }

    public function test_scenario_4_and_5_cancelled_draft_and_soft_deleted_credit_notes_do_not_reduce_balance(): void
    {
        $invoice = $this->createIssuedInvoice(1000.00);

        $this->createCreditNote($invoice, 300.00, 'cancelled');
        $this->createCreditNote($invoice, 200.00, 'draft');
        $deletedCn = $this->createCreditNote($invoice, 150.00, 'draft');
        $deletedCn->delete();

        $invoice->updatePaymentStatus();
        $invoice->refresh();

        $this->assertEquals(1000.00, (float) $invoice->balance);

        $this->paymentService->recordPayment($invoice, ['amount' => 1000.00, 'payment_method' => 'cash']);
        $invoice->refresh();
        $this->assertSame('paid', $invoice->payment_status);
        $this->assertEquals(1000.00, (float) $invoice->total_paid);
        $this->assertEquals(0.00, (float) $invoice->balance);
    }

    public function test_scenario_6_paid_invoice_with_partial_credit_note_and_refund_creates_no_phantom_debt_and_allows_cn_refund(): void
    {
        $invoice = $this->createIssuedInvoice(1000.00);
        $this->paymentService->recordFullPayment($invoice, 'cash');
        $invoice->refresh();
        $this->assertSame('paid', $invoice->payment_status);
        $this->assertEquals(1000.00, (float) $invoice->total_paid);

        // Partial credit note 300 with unpaid refund due
        $creditNote = Invoice::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA1->id,
            'customer_id' => $this->customerA1->id,
            'original_invoice_id' => $invoice->id,
            'type' => 'credit_note',
            'status' => 'valid',
            'payment_status' => 'unpaid',
            'issue_date' => now(),
            'total_excl_tax' => 300.00,
            'total_tax' => 0.00,
            'total_incl_tax' => 300.00,
            'total_paid' => 0.00,
        ]);

        // Credit note's own balance must still be 300.00 so refund payment works
        $this->assertEquals(300.00, (float) $creditNote->balance);
        $refundPayment = $this->paymentService->recordFullPayment($creditNote, 'cash');
        $this->assertSame('refund', $refundPayment->type->value);

        $creditNote->refresh();
        $invoice->updatePaymentStatus();
        $invoice->refresh();

        $this->assertSame('paid', $creditNote->payment_status);
        $this->assertEquals(0.00, (float) $creditNote->balance);

        // Original invoice must have no phantom debt
        $this->assertSame('paid', $invoice->payment_status);
        $this->assertEquals(1000.00, (float) $invoice->total_paid);
        $this->assertEquals(0.00, (float) $invoice->balance);

        $this->expectException(PaymentExceedsBalanceException::class);
        $this->paymentService->recordPayment($invoice, ['amount' => 1.00, 'payment_method' => 'cash']);
    }

    public function test_scenario_7_cross_center_and_cross_tenant_credit_notes_and_payments_are_isolated(): void
    {
        $invoice = $this->createIssuedInvoice(1000.00);

        // Cross-center credit note & payment
        $this->createCreditNote($invoice, 400.00, 'valid', $this->tenantA->id, $this->centerA2->id);
        // Cross-tenant credit note
        $this->createCreditNote($invoice, 500.00, 'valid', $this->tenantB->id, $this->centerB1->id);

        $invoice->updatePaymentStatus();
        $invoice->refresh();

        $this->assertEquals(1000.00, (float) $invoice->balance);
    }

    public function test_scenario_8_mark_as_paid_collects_only_remaining_balance_after_partial_credit_note(): void
    {
        $invoice = $this->createIssuedInvoice(1000.00);
        $this->createCreditNote($invoice, 300.00);
        $invoice->updatePaymentStatus();
        $invoice->refresh();

        $this->assertEquals(700.00, (float) $invoice->balance);

        $payment = $this->paymentService->recordFullPayment($invoice, 'cash');
        $invoice->refresh();

        $this->assertEquals(700.00, (float) $payment->amount);
        $this->assertSame('paid', $invoice->payment_status);
        $this->assertEquals(700.00, (float) $invoice->total_paid);
        $this->assertEquals(0.00, (float) $invoice->balance);

        // Dashboard outstanding must match 0.00
        $dashboard = $this->get('/dashboard?period=month')->assertOk();
        $this->assertEquals(0.00, (float) $dashboard->viewData('page')['props']['stats']['revenue']['outstanding']);
    }
}
