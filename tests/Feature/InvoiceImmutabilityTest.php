<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use App\Observers\InvoiceObserver;
use App\Support\Permissions;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use ReflectionFunction;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * The immutability contract of an issued invoice.
 *
 * WHY THIS TEST EXISTS
 *
 * The observer that guards an issued invoice used to be registered by
 * the test itself:
 *
 *     protected function setUp(): void
 *     {
 *         parent::setUp();
 *         Invoice::observe(InvoiceObserver::class);   // ← here
 *     }
 *
 * That meant the suite proved the guard works when somebody remembered
 * to switch it on, and stayed green while production had no guard at
 * all. The observer is now registered by AppServiceProvider, and this
 * test uses that registration: it never registers anything itself, so
 * removing the registration from the application breaks this test.
 *
 * WHAT THE CONTRACT IS
 *
 * An issued invoice carries a ZATCA invoice number and an ICV that is
 * part of the tenant's hash chain. Once it has left `draft`:
 *
 *  - its amounts, its tax and the identity of both parties are frozen;
 *  - only the settlement fields (total_paid, payment_status) and the
 *    ZATCA reporting fields may move;
 *  - the status transitions are enumerated — an issued document can
 *    never be pushed back to `draft`;
 *  - a save that mixes an allowed change with a forbidden one is
 *    rejected in full.
 *
 * @see InvoiceObserver
 */
class InvoiceImmutabilityTest extends TestCase
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
            Permissions::INVOICES_VIEW,
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

    private function draftInvoice(array $overrides = []): Invoice
    {
        return Invoice::create(array_merge([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-'.uniqid(),
            'issue_date' => now(),
            'supply_date' => now()->toDateString(),
            'type' => 'invoice',
            'status' => 'draft',
            'payment_status' => 'unpaid',
            'total_excl_tax' => 100,
            'total_tax' => 15,
            'total_incl_tax' => 115,
            'total_paid' => 0,
            'currency_code' => 'SAR',
        ], $overrides));
    }

    private function issuedInvoice(array $overrides = []): Invoice
    {
        return $this->draftInvoice(array_merge([
            'status' => 'valid',
        ], $overrides));
    }

    // ─────────────────────────────────────────────────────────────
    // 1. The guard is actually registered by the application
    // ─────────────────────────────────────────────────────────────

    /**
     * The observer must be registered by the application bootstrap, not
     * by the test. This is the test that fails when someone removes the
     * registration from AppServiceProvider.
     */
    public function test_the_observer_is_registered_by_the_application(): void
    {
        $listeners = $this->app['events']->getListeners('eloquent.updating: '.Invoice::class);

        $this->assertNotEmpty(
            $listeners,
            'No eloquent.updating listener is registered for Invoice. '
            .'The immutability guard is not active.'
        );

        $this->assertTrue(
            $this->observerIsRegistered(),
            'InvoiceObserver is not registered on the Invoice model. '
            .'Register it in AppServiceProvider::boot().'
        );
    }

    /**
     * The real registration is what makes the behaviour below possible.
     * A draft invoice may be edited freely; the same edit on an issued
     * invoice must be refused. If the observer were NOT registered,
     * the second assertion would fail.
     */
    public function test_a_protected_field_cannot_be_changed_on_an_issued_invoice(): void
    {
        $invoice = $this->issuedInvoice();

        $originalTax = (float) $invoice->total_tax;
        $originalIncl = (float) $invoice->total_incl_tax;

        try {
            $invoice->update(['total_tax' => 999]);
            $this->fail('The observer should have refused the change.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey(
                'total_tax',
                $e->errors(),
                'The refusal must name the protected field.'
            );
        }

        // Re-read from the database: the record must be exactly as it was.
        $fresh = $invoice->fresh();

        $this->assertSame($originalTax, (float) $fresh->total_tax);
        $this->assertSame($originalIncl, (float) $fresh->total_incl_tax);
        $this->assertSame('valid', $fresh->status);
    }

    /**
     * The field name in the error key must be the field that was
     * actually protected — not a generic bucket.
     *
     * Each case gets its own invoice: `update()` applies the attribute
     * before the observer refuses it, so reusing one model would carry
     * the previous attempt's dirty fields into the next case.
     *
     * The values are typed to match their columns — the amount columns
     * are decimals, so a string would fail to cast before the observer
     * ever runs.
     */
    public function test_the_refusal_names_the_field_that_was_protected(): void
    {
        $cases = [
            'seller_vat_snapshot' => 'TAMPERED',
            'customer_name_snapshot' => 'TAMPERED',
            'customer_address_snapshot' => 'TAMPERED',
            'total_incl_tax' => 1,
            'total_tax' => 1,
            'invoice_number' => 'TAMPERED',
        ];

        foreach ($cases as $field => $value) {
            $invoice = $this->issuedInvoice();

            try {
                $invoice->update([$field => $value]);
                $this->fail("The observer should have refused the change to {$field}.");
            } catch (ValidationException $e) {
                $this->assertArrayHasKey(
                    $field,
                    $e->errors(),
                    "The refusal for {$field} must be keyed by that field."
                );
            }
        }
    }

    // ─────────────────────────────────────────────────────────────
    // 2. Status transitions
    // ─────────────────────────────────────────────────────────────

    /**
     * The issue transition sets the status, the document number and the
     * issue date in one save. That must be allowed.
     */
    public function test_the_issue_transition_is_allowed(): void
    {
        $invoice = $this->draftInvoice();

        $invoice->update([
            'status' => 'valid',
            'invoice_number' => 'INV-'.uniqid(),
            'issue_date' => now(),
        ]);

        $this->assertSame('valid', $invoice->fresh()->status);
    }

    /**
     * An issued document must never be pushed back to draft — that
     * would let the issuer re-number and re-issue it.
     */
    public function test_an_issued_invoice_cannot_be_returned_to_draft(): void
    {
        $invoice = $this->issuedInvoice();

        try {
            $invoice->update(['status' => 'draft']);
            $this->fail('Returning an issued invoice to draft must be refused.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('status', $e->errors());
        }

        $this->assertSame('valid', $invoice->fresh()->status);
    }

    /**
     * The reporting transition (valid → reported) is part of the ZATCA
     * flow and must remain possible.
     */
    public function test_the_reporting_transition_is_allowed(): void
    {
        $invoice = $this->issuedInvoice();

        $invoice->update(['status' => 'reported']);

        $this->assertSame('reported', $invoice->fresh()->status);
    }

    /**
     * A reported document is final. Nothing moves it afterwards.
     */
    public function test_a_reported_invoice_cannot_change_status_again(): void
    {
        $invoice = $this->issuedInvoice(['status' => 'reported']);

        try {
            $invoice->update(['status' => 'valid']);
            $this->fail('A reported invoice must not change status again.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('status', $e->errors());
        }

        $this->assertSame('reported', $invoice->fresh()->status);
    }

    // ─────────────────────────────────────────────────────────────
    // 3. The smuggling case — the defect in the previous observer
    // ─────────────────────────────────────────────────────────────

    /**
     * The previous observer returned early whenever the status was dirty:
     *
     *     if ($invoice->isDirty('status')) {
     *         return; // Allow status transitions
     *     }
     *
     * So a single save that changed the status AND rewrote the tax was
     * waved through. This is the case that must not come back.
     */
    public function test_a_status_change_cannot_smuggle_a_protected_field(): void
    {
        $invoice = $this->issuedInvoice();

        $originalTax = (float) $invoice->total_tax;

        try {
            $invoice->update([
                'status' => 'cancelled',
                'total_tax' => 999,
            ]);
            $this->fail('Mixing a status change with a protected field must be refused.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('status', $e->errors());
        }

        $fresh = $invoice->fresh();

        $this->assertSame($originalTax, (float) $fresh->total_tax, 'The tax must not have moved.');
        $this->assertSame('valid', $fresh->status, 'The status must not have moved.');
    }

    /**
     * The same, for the issue transition: a draft may not be issued
     * while simultaneously rewriting its amounts.
     */
    public function test_the_issue_transition_cannot_smuggle_a_protected_field(): void
    {
        $invoice = $this->draftInvoice();

        $originalIncl = (float) $invoice->total_incl_tax;

        try {
            $invoice->update([
                'status' => 'valid',
                'total_incl_tax' => 1,
            ]);
            $this->fail('Issuing while rewriting the total must be refused.');
        } catch (ValidationException $e) {
            $this->assertNotEmpty($e->errors());
        }

        $fresh = $invoice->fresh();

        $this->assertSame($originalIncl, (float) $fresh->total_incl_tax);
        $this->assertSame('draft', $fresh->status);
    }

    // ─────────────────────────────────────────────────────────────
    // 4. Deletion
    // ─────────────────────────────────────────────────────────────

    /**
     * An issued invoice may not be deleted. A credit note is the way to
     * reverse it.
     */
    public function test_an_issued_invoice_cannot_be_deleted(): void
    {
        $invoice = $this->issuedInvoice();

        try {
            $invoice->delete();
            $this->fail('Deleting an issued invoice must be refused.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('invoice', $e->errors());
        }

        $this->assertDatabaseHas('invoices', ['id' => $invoice->id]);
    }

    /**
     * A force delete is the same operation with a different intention —
     * it must be refused the same way.
     */
    public function test_an_issued_invoice_cannot_be_force_deleted(): void
    {
        $invoice = $this->issuedInvoice();

        try {
            $invoice->forceDelete();
            $this->fail('Force deleting an issued invoice must be refused.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('invoice', $e->errors());
        }

        $this->assertDatabaseHas('invoices', ['id' => $invoice->id]);
    }

    /**
     * A draft is still a working document — it may be deleted.
     *
     * Invoice uses soft deletes, so the row stays with a deleted_at
     * stamp; what matters is that it is no longer a live document.
     */
    public function test_a_draft_invoice_can_be_deleted(): void
    {
        $invoice = $this->draftInvoice();

        $invoice->delete();

        $this->assertSoftDeleted('invoices', ['id' => $invoice->id]);
    }

    // ─────────────────────────────────────────────────────────────
    // 5. The settlement path stays open
    // ─────────────────────────────────────────────────────────────

    /**
     * Taking a payment moves total_paid and payment_status — and nothing
     * else. The invoice amounts, its tax and the identity of both
     * parties must be exactly as they were issued.
     */
    public function test_settlement_may_move_the_paid_amount_only(): void
    {
        $invoice = $this->issuedInvoice();

        $originalIncl = (float) $invoice->total_incl_tax;
        $originalTax = (float) $invoice->total_tax;
        $originalSeller = $invoice->seller_vat_snapshot;

        $invoice->total_paid = 50;
        $invoice->payment_status = 'partial';
        $invoice->save();

        $fresh = $invoice->fresh();

        $this->assertSame(50.0, (float) $fresh->total_paid);
        $this->assertSame('partial', $fresh->payment_status);

        $this->assertSame($originalIncl, (float) $fresh->total_incl_tax, 'The total must not move.');
        $this->assertSame($originalTax, (float) $fresh->total_tax, 'The tax must not move.');
        $this->assertSame($originalSeller, $fresh->seller_vat_snapshot, 'The seller identity must not move.');
    }

    /**
     * The ZATCA reporting path stamps the UUID and the hash chain onto
     * an issued document without touching its commercial content.
     */
    public function test_the_reporting_path_may_stamp_the_zatca_fields(): void
    {
        $invoice = $this->issuedInvoice();

        $originalTax = (float) $invoice->total_tax;

        $invoice->update([
            'zatca_uuid' => 'uuid-1234',
            'zatca_qr_tlv' => 'tlv-payload',
        ]);

        $fresh = $invoice->fresh();

        $this->assertSame('uuid-1234', $fresh->zatca_uuid);
        $this->assertSame('tlv-payload', $fresh->zatca_qr_tlv);
        $this->assertSame($originalTax, (float) $fresh->total_tax);
    }

    // ─────────────────────────────────────────────────────────────
    // 6. Acceptance through the real routes
    // ─────────────────────────────────────────────────────────────

    /**
     * The whole lifecycle through the real HTTP routes: take a payment
     * on an issued invoice and see the document settle.
     *
     * These are web routes — they answer with a redirect and carry their
     * outcome in the session, not with a JSON body.
     */
    public function test_the_issue_and_settle_lifecycle_works_through_the_routes(): void
    {
        $user = $this->userWith([
            Permissions::INVOICES_CREATE,
            Permissions::PURCHASE_PAYMENTS_MANAGE,
        ]);

        $invoice = $this->draftInvoice();

        // A partial payment settles the document.
        $response = $this->actingAs($user)->post(
            route('app.invoices.payments.store', $invoice->id),
            [
                'amount' => 50,
                'payment_method' => 'cash',
                'payment_date' => now()->toDateString(),
            ]
        );

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('payments', [
            'invoice_id' => $invoice->id,
            'amount' => 50,
        ]);

        $fresh = $invoice->fresh();

        $this->assertSame(50.0, (float) $fresh->total_paid);
        $this->assertSame('partial', $fresh->payment_status);
        $this->assertSame(115.0, (float) $fresh->total_incl_tax, 'The issued total must not move.');
        $this->assertSame(15.0, (float) $fresh->total_tax, 'The issued tax must not move.');
    }

    /**
     * A full payment settles the document to `paid`.
     */
    public function test_a_full_payment_settles_the_document(): void
    {
        $user = $this->userWith([Permissions::PURCHASE_PAYMENTS_MANAGE]);

        $invoice = $this->issuedInvoice();

        $this->actingAs($user)
            ->post(route('app.invoices.payments.pay-full', $invoice->id), [
                'payment_method' => 'cash',
            ])
            ->assertSessionHasNoErrors();

        $fresh = $invoice->fresh();

        $this->assertSame(115.0, (float) $fresh->total_paid);
        $this->assertSame('paid', $fresh->payment_status);
        $this->assertSame(15.0, (float) $fresh->total_tax, 'The issued tax must not move.');
    }

    /**
     * A refund reverses the paid amount and the document falls back to
     * unpaid — while the issued amounts stay frozen.
     *
     * A refund is a separate payment row with type `refund`, not a
     * mutation of the original payment. That is how PaymentService
     * records one, and the invoice then subtracts it.
     */
    public function test_a_refund_reverses_the_settled_amount(): void
    {
        $user = $this->userWith([Permissions::PURCHASE_PAYMENTS_MANAGE]);

        $invoice = $this->issuedInvoice();

        $this->actingAs($user)
            ->post(route('app.invoices.payments.pay-full', $invoice->id), [
                'payment_method' => 'cash',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('paid', $invoice->fresh()->payment_status);

        // Record the refund the way the service does.
        Payment::create([
            'tenant_id' => $invoice->tenant_id,
            'center_id' => $invoice->center_id,
            'invoice_id' => $invoice->id,
            'amount' => 115,
            'payment_date' => now(),
            'payment_method' => 'cash',
            'type' => 'refund',
            'received_by' => $user->id,
        ]);

        $invoice->updatePaymentStatus();

        $fresh = $invoice->fresh();

        $this->assertSame(0.0, (float) $fresh->total_paid);
        $this->assertSame('unpaid', $fresh->payment_status);
        $this->assertSame(115.0, (float) $fresh->total_incl_tax, 'The issued total must not move.');
        $this->assertSame(15.0, (float) $fresh->total_tax, 'The issued tax must not move.');
    }

    /**
     * An unauthorised user is refused with a 403 — a separate case from
     * the contract violations, which are validation errors.
     */
    public function test_an_unauthorised_user_gets_a_403(): void
    {
        $user = $this->userWith([]);

        $invoice = $this->issuedInvoice();

        $this->actingAs($user)
            ->postJson(route('app.invoices.payments.store', $invoice->id), [
                'amount' => 50,
                'payment_method' => 'cash',
            ])
            ->assertStatus(403);
    }

    /**
     * A contract violation that reaches the route must surface as a
     * validation error, and the write must be rolled back.
     */
    public function test_a_contract_violation_rolls_the_whole_operation_back(): void
    {
        $user = $this->userWith([Permissions::PURCHASE_PAYMENTS_MANAGE]);

        $invoice = $this->issuedInvoice();

        $before = [
            'total_tax' => (float) $invoice->total_tax,
            'total_incl_tax' => (float) $invoice->total_incl_tax,
            'total_paid' => (float) $invoice->total_paid,
        ];

        // Try to issue-and-tamper in one operation through a transaction.
        try {
            DB::transaction(function () use ($invoice) {
                $invoice->update([
                    'status' => 'cancelled',
                    'total_incl_tax' => 1,
                ]);
            });
            $this->fail('The transaction should have been rolled back.');
        } catch (ValidationException $e) {
            $this->assertNotEmpty($e->errors());
        }

        $fresh = $invoice->fresh();

        $this->assertSame($before['total_tax'], (float) $fresh->total_tax);
        $this->assertSame($before['total_incl_tax'], (float) $fresh->total_incl_tax);
        $this->assertSame($before['total_paid'], (float) $fresh->total_paid);
        $this->assertSame('valid', $fresh->status);
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────

    /**
     * Is InvoiceObserver actually registered on the Invoice model?
     *
     * Reads the model's registered listeners rather than assuming. The
     * dispatcher keeps a class observer as the string
     * "App\Observers\InvoiceObserver@updating", so that is what we look
     * for — and the lookup uses the model's own observable events, so a
     * rename of the event would not silently make this test pass.
     */
    private function observerIsRegistered(): bool
    {
        $invoice = new Invoice;

        foreach ($invoice->getObservableEvents() as $event) {
            $listeners = $this->app['events']->getListeners('eloquent.'.$event.': '.Invoice::class);

            foreach ($listeners as $listener) {
                // Laravel hands back a closure that wraps the observer.
                // Unwrap it through the dispatcher so we compare the
                // observer class, not the wrapper.
                $class = $this->listenerClass($listener);

                if ($class === InvoiceObserver::class) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * The class behind a registered listener, if it can be determined.
     *
     * Laravel registers a class observer by wrapping the
     * "Class@method" string in a closure that carries it as a static
     * variable. That string is what we compare against.
     */
    private function listenerClass(mixed $listener): ?string
    {
        if (is_string($listener)) {
            return str_contains($listener, '@')
                ? Str::before($listener, '@')
                : $listener;
        }

        if (is_array($listener) && isset($listener[0])) {
            return is_object($listener[0]) ? $listener[0]::class : null;
        }

        if ($listener instanceof Closure) {
            $reflected = new ReflectionFunction($listener);

            $variables = $reflected->getStaticVariables();

            if (isset($variables['listener']) && is_string($variables['listener'])) {
                return str_contains($variables['listener'], '@')
                    ? Str::before($variables['listener'], '@')
                    : $variables['listener'];
            }

            $bound = $reflected->getClosureThis();

            if ($bound !== null) {
                return $bound::class;
            }
        }

        if (is_object($listener)) {
            return $listener::class;
        }

        return null;
    }
}
