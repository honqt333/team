<?php

declare(strict_types=1);

namespace Tests\Feature\LaunchAudit;

use App\Models\Center;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Throwable;

/**
 * LAUNCH AUDIT — financial integrity of the sales invoice.
 *
 * WHAT IS BEING PROVEN
 * --------------------
 * A money document has exactly one writer. The invoice has THREE layers of
 * protection, and this file proves each of them separately, because the
 * repository's own `InvoiceImmutabilityTest` currently proves them
 * against an API the model no longer supports (see the note below) and
 * therefore fails on 15 of 25 cases.
 *
 *   Layer 1 — mass assignment. `Invoice::$fillable` (app/Models/Invoice.php:29)
 *             does not list `status`, `payment_status` or the money
 *             columns, and `Invoice::fill()` (app/Models/Invoice.php:88)
 *             strips them again for an existing record.
 *   Layer 2 — the observer. `InvoiceObserver::updating()`
 *             (app/Observers/InvoiceObserver.php:197) enumerates the
 *             legal transitions and the fields each transition may write.
 *   Layer 3 — the database. The money columns are `decimal`, not `float`.
 *
 * THE FAILURE MODE THAT MATTERS
 * -----------------------------
 * Layer 1 wins by DISCARDING the value silently. Layer 2 wins by
 * THROWING. The two layers therefore behave differently for the same
 * mistake: `update(['status' => 'cancelled'])` is a silent no-op, while
 * `setAttribute('status', 'cancelled')` is a loud refusal.
 *
 * The invariant holds in both cases — the money cannot be rewritten — but
 * a silent no-op inside a financial flow is a trap: a developer (or an
 * agent) who writes `update(['status' => 'reported'])` gets no error and
 * no effect. Two of the repository's red tests are exactly this shape.
 * These tests pin BOTH behaviours so the difference can never be mistaken
 * for a working feature.
 */
class FinancialIntegrityAuditTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Center $center;

    private User $user;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ([
            Permissions::INVOICES_VIEW,
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

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
        ]);
        $this->user->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
        $this->user->givePermissionTo([
            Permissions::INVOICES_VIEW,
            Permissions::PURCHASE_PAYMENTS_MANAGE,
        ]);
        $this->user->refresh();
    }

    private function invoice(string $status = 'valid', float $total = 115.0): Invoice
    {
        return Invoice::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-'.uniqid(),
            'issue_date' => now(),
            'supply_date' => now()->toDateString(),
            'type' => 'invoice',
            'status' => $status,
            'payment_status' => 'unpaid',
            'total_excl_tax' => 100,
            'total_tax' => 15,
            'total_incl_tax' => $total,
            'total_paid' => 0,
            'currency_code' => 'SAR',
        ]);
    }

    // ── Layer 1: mass assignment ───────────────────────────────────────

    public function test_mass_assignment_cannot_smuggle_a_total_into_an_issued_invoice(): void
    {
        $invoice = $this->invoice('valid', 115.0);

        $invoice->update([
            'total_incl_tax' => 1.00,
            'total_excl_tax' => 1.00,
            'total_tax' => 0.00,
            'total_paid' => 999.00,
        ]);

        $fresh = $invoice->fresh();

        $this->assertSame(115.0, (float) $fresh->total_incl_tax, 'The invoice total must not be mass-assignable.');
        $this->assertSame(100.0, (float) $fresh->total_excl_tax);
        $this->assertSame(15.0, (float) $fresh->total_tax);
        $this->assertSame(0.0, (float) $fresh->total_paid);
    }

    public function test_mass_assignment_cannot_smuggle_a_payment_status(): void
    {
        $invoice = $this->invoice('valid');

        $invoice->update(['payment_status' => 'paid']);

        $this->assertSame('unpaid', $invoice->fresh()->payment_status);
    }

    /**
     * Characterisation of the SILENT failure mode.
     *
     * The status does not move — the guard holds. But nothing is raised
     * either: `fill()` drops the key and `save()` has nothing to police.
     * A caller therefore gets a successful save and a document that did
     * not change. This is the exact shape of the two red tests in the
     * repository (`the issue transition may set the number and the date`
     * and `the legitimate reporting and cancellation transitions succeed`).
     */
    public function test_a_mass_assigned_status_change_is_a_silent_no_op(): void
    {
        $invoice = $this->invoice('valid');

        $raised = null;

        try {
            $invoice->update(['status' => 'cancelled']);
        } catch (Throwable $e) {
            $raised = $e;
        }

        $this->assertNull(
            $raised,
            'Mass assignment discards the status silently — no error reaches the caller.'
        );
        $this->assertSame(
            'valid',
            $invoice->fresh()->status,
            'The status must not move. (This is the guard working, not the feature working.)'
        );
    }

    // ── Layer 2: the observer ─────────────────────────────────────────

    public function test_writing_a_protected_total_directly_is_refused_loudly(): void
    {
        $invoice = $this->invoice('valid');

        $raised = null;

        try {
            $invoice->setAttribute('total_incl_tax', 1.00);
            $invoice->save();
        } catch (ValidationException $e) {
            $raised = $e;
        }

        $this->assertNotNull($raised, 'A direct write to a protected total must be refused loudly.');
        $this->assertSame(115.0, (float) $invoice->fresh()->total_incl_tax);
    }

    public function test_an_issued_invoice_cannot_be_returned_to_draft(): void
    {
        $invoice = $this->invoice('valid');

        $raised = null;

        try {
            $invoice->setAttribute('status', 'draft');
            $invoice->save();
        } catch (ValidationException $e) {
            $raised = $e;
        }

        $this->assertNotNull($raised, 'valid → draft is not a legal transition.');
        $this->assertSame('valid', $invoice->fresh()->status);
    }

    public function test_the_issue_transition_cannot_smuggle_a_total(): void
    {
        $invoice = $this->invoice('draft');

        $raised = null;

        try {
            $invoice->setAttribute('status', 'valid');
            $invoice->setAttribute('invoice_number', 'INV-SMUGGLED');
            $invoice->setAttribute('total_incl_tax', 1.00);
            $invoice->save();
        } catch (ValidationException $e) {
            $raised = $e;
        }

        $this->assertNotNull($raised, 'The issue transition may not carry a money column.');
        $this->assertSame('draft', $invoice->fresh()->status, 'The rejected transition must leave the draft alone.');
        $this->assertSame(115.0, (float) $invoice->fresh()->total_incl_tax);
    }

    public function test_a_draft_invoice_may_still_be_cancelled(): void
    {
        $invoice = $this->invoice('draft');

        $invoice->setAttribute('status', 'cancelled');
        $invoice->save();

        $this->assertSame('cancelled', $invoice->fresh()->status);
    }

    public function test_a_reported_invoice_is_final(): void
    {
        $invoice = $this->invoice('valid');

        // The reporting transition itself is legal.
        $invoice->setAttribute('status', 'reported');
        $invoice->save();
        $this->assertSame('reported', $invoice->fresh()->status);

        $raised = null;

        try {
            $invoice->setAttribute('status', 'cancelled');
            $invoice->save();
        } catch (ValidationException $e) {
            $raised = $e;
        }

        $this->assertNotNull($raised, 'A reported document must not move again.');
        $this->assertSame('reported', $invoice->fresh()->status);
    }

    public function test_an_issued_invoice_cannot_be_deleted(): void
    {
        $invoice = $this->invoice('valid');

        $raised = null;

        try {
            $invoice->delete();
        } catch (ValidationException $e) {
            $raised = $e;
        }

        $this->assertNotNull($raised, 'An issued document must not be deletable.');
        $this->assertNotNull($invoice->fresh());
    }

    // ── Settlement ────────────────────────────────────────────────────

    public function test_a_payment_cannot_exceed_the_outstanding_balance(): void
    {
        $invoice = $this->invoice('valid', 115.0);

        $response = $this->actingAs($this->user)
            ->post('/app/invoices/'.$invoice->id.'/payments', [
                'amount' => 10000,
                'payment_method' => 'cash',
            ]);

        $response->assertSessionHasErrors('amount');

        $fresh = $invoice->fresh();
        $this->assertSame(0.0, (float) $fresh->total_paid, 'An over-payment must not be recorded.');
        $this->assertLessThanOrEqual(
            (float) $fresh->total_incl_tax,
            (float) $fresh->total_paid,
            'The paid amount may never exceed the invoice total.'
        );
    }

    public function test_the_payment_status_follows_the_payments(): void
    {
        $invoice = $this->invoice('valid', 115.0);

        $this->actingAs($this->user)
            ->post('/app/invoices/'.$invoice->id.'/payments', [
                'amount' => 50,
                'payment_method' => 'cash',
            ])
            ->assertSessionHasNoErrors();

        $fresh = $invoice->fresh();
        $this->assertSame(50.0, (float) $fresh->total_paid);
        $this->assertSame('partial', $fresh->payment_status);

        $this->actingAs($this->user)
            ->post('/app/invoices/'.$invoice->id.'/payments', [
                'amount' => 65,
                'payment_method' => 'cash',
            ])
            ->assertSessionHasNoErrors();

        $fresh = $invoice->fresh();
        $this->assertSame(115.0, (float) $fresh->total_paid);
        $this->assertSame('paid', $fresh->payment_status);
        $this->assertSame(0.0, (float) $fresh->balance);
    }
}
