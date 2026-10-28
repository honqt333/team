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
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Tests for the concurrent collection of a payment (audit finding #23).
 *
 * THE BUG
 *
 * PaymentsController::store() compared the amount with the balance of
 * the invoice it had loaded, and then handed the request to
 * PaymentService::recordPayment(). The service opened a transaction,
 * but it never locked the invoice row and never re-read the balance
 * inside that transaction:
 *
 *     $payment = Payment::create([...]);
 *     $invoice->updatePaymentStatus();
 *
 * So two collectors reaching the same invoice at the same time could
 * both be accepted against the same outstanding balance, and the
 * invoice could end up with a `total_paid` that is larger than the
 * invoice total.
 *
 * THE FIX
 *
 * The service locks the invoice row (`lockForUpdate()`) and re-reads
 * the balance from the database inside the transaction before it
 * accepts the amount. The row lock takes effect on MySQL/InnoDB — the
 * production database. SQLite has no row locks, so what these tests
 * pin down is the re-read and the refusal, not the lock itself.
 */
class PaymentConcurrencySafetyTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Center $center;

    private Customer $customer;

    private PaymentService $paymentService;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ([
            Permissions::INVOICES_CREATE,
            Permissions::PURCHASE_PAYMENTS_MANAGE,
        ] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $this->tenant = Tenant::factory()->create();
        $this->center = Center::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
        ]);

        $this->paymentService = app(PaymentService::class);
    }

    private function userWith(array $permissions): User
    {
        $user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
        ]);

        $user->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);

        foreach ($permissions as $permission) {
            $user->givePermissionTo($permission);
        }

        $user->refresh();

        return $user;
    }

    /**
     * An issued invoice of 115 SAR (100 + 15 VAT), nothing paid yet.
     */
    private function invoice(array $overrides = []): Invoice
    {
        return Invoice::create(array_merge([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-'.uniqid(),
            'issue_date' => now(),
            'supply_date' => now()->toDateString(),
            'type' => 'invoice',
            'status' => 'valid',
            'payment_status' => 'unpaid',
            'total_excl_tax' => 100,
            'total_tax' => 15,
            'total_incl_tax' => 115,
            'total_paid' => 0,
            'currency_code' => 'SAR',
        ], $overrides));
    }

    /**
     * A payment larger than the outstanding balance is refused.
     */
    public function test_a_payment_larger_than_the_balance_is_refused(): void
    {
        $invoice = $this->invoice();

        $this->expectException(PaymentExceedsBalanceException::class);

        try {
            $this->paymentService->recordPayment($invoice, [
                'amount' => 150,
                'payment_method' => 'cash',
            ]);
        } finally {
            $this->assertDatabaseCount('payments', 0);

            $invoice->refresh();
            $this->assertEquals(0, (float) $invoice->total_paid);
            $this->assertSame('unpaid', $invoice->payment_status);
        }
    }

    /**
     * The balance is re-read inside the transaction, so a collector
     * holding a stale invoice cannot spend money that is already gone.
     */
    public function test_the_balance_is_re_read_inside_the_transaction(): void
    {
        $invoice = $this->invoice();

        // The first collector loads the invoice — the balance is 115.
        $staleCopy = Invoice::find($invoice->id);

        $this->assertEquals(115, (float) $staleCopy->balance);

        // A second collector pays 100 of it in the meantime.
        $this->paymentService->recordPayment($invoice->fresh(), [
            'amount' => 100,
            'payment_method' => 'cash',
        ]);

        // The first collector now tries to settle the whole 115 it saw.
        $this->expectException(PaymentExceedsBalanceException::class);

        try {
            $this->paymentService->recordPayment($staleCopy, [
                'amount' => 115,
                'payment_method' => 'cash',
            ]);
        } finally {
            $invoice->refresh();

            $this->assertEquals(100, (float) $invoice->total_paid);
            $this->assertLessThanOrEqual(
                (float) $invoice->total_incl_tax,
                (float) $invoice->total_paid,
                'The invoice must never hold more paid than it is worth.'
            );
        }
    }

    /**
     * Paying in full records exactly what is left — not the balance the
     * caller happened to hold.
     */
    public function test_paying_in_full_records_the_remaining_balance_only(): void
    {
        $invoice = $this->invoice();

        $this->paymentService->recordPayment($invoice->fresh(), [
            'amount' => 40,
            'payment_method' => 'cash',
        ]);

        $this->paymentService->recordFullPayment($invoice->fresh(), 'cash');

        $invoice->refresh();

        $this->assertEquals(115, (float) $invoice->total_paid);
        $this->assertSame('paid', $invoice->payment_status);
        $this->assertEquals(75, (float) Payment::where('invoice_id', $invoice->id)->sum('amount') - 40);
    }

    /**
     * Two "pay full" requests cannot both settle the same invoice.
     */
    public function test_a_second_pay_full_cannot_settle_the_same_invoice_twice(): void
    {
        $invoice = $this->invoice();

        $this->paymentService->recordFullPayment($invoice->fresh(), 'cash');

        $this->expectException(PaymentExceedsBalanceException::class);

        try {
            $this->paymentService->recordFullPayment($invoice->fresh(), 'cash');
        } finally {
            $invoice->refresh();

            $this->assertEquals(115, (float) $invoice->total_paid);
            $this->assertSame('paid', $invoice->payment_status);
            $this->assertEquals(1, Payment::where('invoice_id', $invoice->id)->count());
        }
    }

    /**
     * A payment of zero or less is refused outright.
     */
    public function test_a_non_positive_payment_is_refused(): void
    {
        $invoice = $this->invoice();

        $this->expectException(InvalidArgumentException::class);

        try {
            $this->paymentService->recordPayment($invoice, [
                'amount' => 0,
                'payment_method' => 'cash',
            ]);
        } finally {
            $this->assertDatabaseCount('payments', 0);
        }
    }

    /**
     * A normal payment still works and moves the invoice to partial.
     */
    public function test_a_normal_payment_still_works(): void
    {
        $invoice = $this->invoice();

        $this->paymentService->recordPayment($invoice->fresh(), [
            'amount' => 50,
            'payment_method' => 'cash',
        ]);

        $invoice->refresh();

        $this->assertEquals(50, (float) $invoice->total_paid);
        $this->assertSame('partial', $invoice->payment_status);
    }

    /**
     * The HTTP endpoint refuses an amount above the balance.
     */
    public function test_the_endpoint_refuses_an_amount_above_the_balance(): void
    {
        $invoice = $this->invoice();

        $user = $this->userWith([Permissions::PURCHASE_PAYMENTS_MANAGE]);

        $response = $this->actingAs($user)->post('/app/invoices/'.$invoice->id.'/payments', [
            'amount' => 500,
            'payment_method' => 'cash',
        ]);

        $response->assertSessionHasErrors('amount');
        $this->assertDatabaseCount('payments', 0);

        $invoice->refresh();
        $this->assertEquals(0, (float) $invoice->total_paid);
    }

    /**
     * A second collector that arrives while the invoice is already
     * settled gets an error and no payment row.
     */
    public function test_a_collector_arriving_after_the_invoice_was_settled_gets_an_error(): void
    {
        $invoice = $this->invoice();

        $user = $this->userWith([Permissions::PURCHASE_PAYMENTS_MANAGE]);

        $this->actingAs($user)->post('/app/invoices/'.$invoice->id.'/pay-full', [
            'payment_method' => 'cash',
        ])->assertSessionHasNoErrors();

        $response = $this->actingAs($user)->post('/app/invoices/'.$invoice->id.'/payments', [
            'amount' => 10,
            'payment_method' => 'cash',
        ]);

        // The invoice is settled, so the policy refuses the request
        // outright (recordPayment requires an unsettled invoice).
        $response->assertForbidden();
        $this->assertEquals(1, Payment::where('invoice_id', $invoice->id)->count());

        $invoice->refresh();
        $this->assertEquals(115, (float) $invoice->total_paid);
    }
}
