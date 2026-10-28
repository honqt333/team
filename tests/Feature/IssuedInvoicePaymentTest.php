<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Tests for collecting a payment on an ISSUED invoice
 * (audit finding #9).
 *
 * THE BUG
 *
 * PaymentsController::store() and ::payFull() authorized with
 * `$this->authorize('update', $invoice)`.
 *
 * `update` is the DRAFT-EDIT ability — InvoicePolicy::update requires
 * `status === 'draft'`:
 *
 *     return $user->can(Permissions::INVOICES_CREATE)
 *         && $user->tenant_id === $invoice->tenant_id
 *         && $invoice->status === 'draft';
 *
 * An issued invoice has status `valid` (InvoiceService::issueInvoice
 * moves it out of draft). So the request was rejected with a 403 even
 * though the invoice was unpaid and the customer was at the counter —
 * the money could not be taken.
 *
 * The policy already had the right ability for this and nothing used
 * it:
 *
 *     public function recordPayment(User $user, Invoice $invoice): bool
 *     {
 *         return $user->can(Permissions::PURCHASE_PAYMENTS_MANAGE)
 *             && $user->tenant_id === $invoice->tenant_id
 *             && $invoice->payment_status !== 'paid';
 *     }
 *
 * THE FIX
 *
 * The two collection endpoints authorize with `recordPayment`, and the
 * delete endpoint uses PaymentPolicy::delete instead of the invoice's
 * draft-edit ability (a wrong entry on an issued invoice could
 * otherwise never be removed).
 */
class IssuedInvoicePaymentTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Center $center;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ([
            Permissions::INVOICES_CREATE,
            Permissions::PURCHASE_PAYMENTS_MANAGE,
            Permissions::PAYMENTS_DELETE,
        ] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $this->tenant = Tenant::factory()->create();
        $this->center = Center::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->customer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
        ]);
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
     * An ISSUED invoice: status `valid`, not a draft.
     */
    private function issuedInvoice(array $overrides = []): Invoice
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
     * The headline case: a payment on an issued invoice must succeed.
     */
    public function test_a_payment_can_be_recorded_on_an_issued_invoice(): void
    {
        $invoice = $this->issuedInvoice();

        $user = $this->userWith([Permissions::PURCHASE_PAYMENTS_MANAGE]);

        $response = $this->actingAs($user)->post('/app/invoices/'.$invoice->id.'/payments', [
            'amount' => 50,
            'payment_method' => 'cash',
            'payment_date' => now()->toDateString(),
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('payments', [
            'invoice_id' => $invoice->id,
            'amount' => 50,
        ]);

        $invoice->refresh();

        $this->assertSame(
            'partial',
            $invoice->payment_status,
            'A part payment must move the invoice to partial.'
        );
        $this->assertEquals(50, (float) $invoice->total_paid);
    }

    /**
     * Paying an issued invoice in full must work too.
     */
    public function test_an_issued_invoice_can_be_paid_in_full(): void
    {
        $invoice = $this->issuedInvoice();

        $user = $this->userWith([Permissions::PURCHASE_PAYMENTS_MANAGE]);

        $response = $this->actingAs($user)->post('/app/invoices/'.$invoice->id.'/pay-full', [
            'payment_method' => 'cash',
        ]);

        $response->assertSessionHasNoErrors();

        $invoice->refresh();

        $this->assertSame('paid', $invoice->payment_status);
        $this->assertEquals(115, (float) $invoice->total_paid);
    }

    /**
     * The draft flow keeps working — the fix must not open the
     * draft-edit door for payments.
     */
    public function test_a_payment_on_a_draft_invoice_still_works(): void
    {
        $invoice = $this->issuedInvoice(['status' => 'draft']);

        // Both permissions here, so this test isolates the
        // draft/issued distinction and passes before AND after the
        // fix — it is the "no regression for drafts" guard, not
        // another copy of the issued-invoice case.
        $user = $this->userWith([
            Permissions::PURCHASE_PAYMENTS_MANAGE,
            Permissions::INVOICES_CREATE,
        ]);

        $response = $this->actingAs($user)->post('/app/invoices/'.$invoice->id.'/payments', [
            'amount' => 50,
            'payment_method' => 'cash',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('payments', ['invoice_id' => $invoice->id]);
    }

    /**
     * Without the payment permission the request is still refused.
     */
    public function test_a_user_without_the_payment_permission_is_refused(): void
    {
        $invoice = $this->issuedInvoice();

        $user = $this->userWith([Permissions::INVOICES_CREATE]);

        $response = $this->actingAs($user)->post('/app/invoices/'.$invoice->id.'/payments', [
            'amount' => 50,
            'payment_method' => 'cash',
        ]);

        $response->assertForbidden();

        $this->assertDatabaseMissing('payments', ['invoice_id' => $invoice->id]);
    }

    /**
     * A user from another tenant cannot touch the invoice.
     */
    public function test_a_user_from_another_tenant_is_refused(): void
    {
        $invoice = $this->issuedInvoice();

        $otherTenant = Tenant::factory()->create();
        $otherCenter = Center::factory()->create(['tenant_id' => $otherTenant->id]);

        $user = User::factory()->create([
            'tenant_id' => $otherTenant->id,
            'current_center_id' => $otherCenter->id,
        ]);
        $user->centers()->attach($otherCenter->id, ['tenant_id' => $otherTenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($otherTenant->id);
        $user->givePermissionTo(Permissions::PURCHASE_PAYMENTS_MANAGE);
        $user->refresh();

        $response = $this->actingAs($user)->post('/app/invoices/'.$invoice->id.'/payments', [
            'amount' => 50,
            'payment_method' => 'cash',
        ]);

        // 404, not 403: the route model binding scopes the invoice to
        // the current tenant, so a foreign invoice is simply not
        // found. That is the better answer — a 403 would confirm the
        // invoice exists.
        $response->assertNotFound();
        $this->assertDatabaseMissing('payments', ['invoice_id' => $invoice->id]);
    }

    /**
     * An already paid invoice takes no further payment.
     */
    public function test_a_paid_invoice_refuses_a_further_payment(): void
    {
        $invoice = $this->issuedInvoice([
            'payment_status' => 'paid',
            'total_paid' => 115,
        ]);

        $user = $this->userWith([Permissions::PURCHASE_PAYMENTS_MANAGE]);

        $response = $this->actingAs($user)->post('/app/invoices/'.$invoice->id.'/payments', [
            'amount' => 10,
            'payment_method' => 'cash',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('payments', ['invoice_id' => $invoice->id]);
    }

    /**
     * A wrong entry on an ISSUED invoice must be removable. The delete
     * endpoint used to authorize against the invoice's draft-edit
     * ability, which an issued invoice never satisfied.
     */
    public function test_a_payment_on_an_issued_invoice_can_be_deleted(): void
    {
        $invoice = $this->issuedInvoice();

        $payment = Payment::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'invoice_id' => $invoice->id,
            'amount' => 50,
            'payment_date' => now(),
            'payment_method' => 'cash',
            'type' => 'payment',
        ]);

        $user = $this->userWith([Permissions::PAYMENTS_DELETE]);

        $response = $this->actingAs($user)->delete('/app/payments/'.$payment->id);

        $response->assertSessionHasNoErrors();

        // The Payment model uses SoftDeletes, so the row stays with a
        // deleted_at stamp.
        $this->assertSoftDeleted('payments', ['id' => $payment->id]);

        $invoice->refresh();
        $this->assertEquals(0, (float) $invoice->total_paid);
    }

    /**
     * Deleting a payment still needs the delete permission.
     */
    public function test_deleting_a_payment_needs_the_delete_permission(): void
    {
        $invoice = $this->issuedInvoice();

        $payment = Payment::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'invoice_id' => $invoice->id,
            'amount' => 50,
            'payment_date' => now(),
            'payment_method' => 'cash',
            'type' => 'payment',
        ]);

        $user = $this->userWith([Permissions::PURCHASE_PAYMENTS_MANAGE]);

        $response = $this->actingAs($user)->delete('/app/payments/'.$payment->id);

        $response->assertForbidden();
        $this->assertDatabaseHas('payments', ['id' => $payment->id]);
    }
}
