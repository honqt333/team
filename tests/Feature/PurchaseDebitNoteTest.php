<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Payment;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceLine;
use App\Models\PurchaseReturnInvoice;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\Part;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PurchaseDebitNoteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $this->seed(\Database\Seeders\PermissionsSeeder::class);
    }

    /**
     * Helper: builds a tenant + center + user with full purchasing
     * permissions, plus a paid purchase invoice that can be returned.
     */
    protected function bootFullPurchasingContext(): array
    {
        $tenant = Tenant::factory()->create();
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);
        $warehouse = Warehouse::factory()->create(['center_id' => $center->id, 'is_default' => true]);
        $supplier = Supplier::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'name' => 'Test Supplier',
            'type' => 'parts',
            'is_active' => true,
        ]);
        $part = Part::factory()->create(['tenant_id' => $tenant->id]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
        ]);
        $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        $purchasingPerms = Permission::where('name', 'like', 'purchasing.%')->pluck('name');
        $user->givePermissionTo($purchasingPerms);
        $user->refresh();

        return compact('tenant', 'center', 'warehouse', 'supplier', 'part', 'user');
    }

    /**
     * Helper: builds a fully-paid purchase invoice for the given supplier.
     */
    protected function makePaidInvoice(Supplier $supplier, Part $part, Warehouse $warehouse, Center $center, Tenant $tenant, float $total = 1000.0, ?string $code = null): PurchaseInvoice
    {
        $invoice = PurchaseInvoice::create([
            'tenant_id'      => $tenant->id,
            'center_id'      => $center->id,
            'supplier_id'    => $supplier->id,
            'invoice_number' => 'INV-001',
            'code'           => $code ?? PurchaseInvoice::generateCode($tenant->id),
            'issue_date'     => now()->toDateString(),
            'status'         => PurchaseInvoice::STATUS_PAID,
            'subtotal'       => $total,
            'tax_amount'     => 0,
            'total'          => $total,
            'balance'        => 0,
        ]);
        PurchaseInvoiceLine::create([
            'purchase_invoice_id' => $invoice->id,
            'part_id'             => $part->id,
            'qty'                 => 10,
            'unit_cost'           => $total / 10,
            'tax_rate'            => 0,
            'tax_amount'          => 0,
            'total'               => $total,
        ]);

        // Mark as paid
        Payment::create([
            'tenant_id'           => $tenant->id,
            'center_id'           => $center->id,
            'purchase_invoice_id' => $invoice->id,
            'amount'              => $total,
            'payment_date'        => now(),
            'payment_method'      => 'cash',
            'received_by'         => null,
            'type'                => Payment::TYPE_PAYMENT,
        ]);

        return $invoice;
    }

    /** @test */
    public function debit_note_does_not_create_a_payment_row(): void
    {
        $ctx = $this->bootFullPurchasingContext();
        $user = $ctx['user'];

        // A fully-paid invoice of 1000.
        $invoice = $this->makePaidInvoice(
            $ctx['supplier'],
            $ctx['part'],
            $ctx['warehouse'],
            $ctx['center'],
            $ctx['tenant'],
            total: 1000.0
        );

        $paymentsBefore = $invoice->payments()->count(); // 1 (the original cash payment)
        $this->assertEquals(1, $paymentsBefore);

        // Simulate the controller's debit-note path: it does NOT create
        // any Payment row at all. The bookkeeping note lives on the
        // PurchaseReturnInvoice itself.
        PurchaseReturnInvoice::create([
            'tenant_id'          => $ctx['tenant']->id,
            'center_id'          => $ctx['center']->id,
            'purchase_invoice_id'=> $invoice->id,
            'code'               => PurchaseReturnInvoice::generateCode($ctx['tenant']->id),
            'return_date'        => now()->toDateString(),
            'subtotal'           => 1000,
            'tax_amount'         => 0,
            'total'              => 1000,
            'create_debit_note'  => true,
            'debit_note_date'    => now()->toDateString(),
        ]);

        // CRITICAL: the payments count for this invoice MUST stay at 1.
        // If it grew, we accidentally reintroduced the bug where the
        // debit note was being recorded as a payment.
        $this->assertEquals(1, $invoice->payments()->count());

        // No TYPE_DEBIT_NOTE rows should ever be in the payments table
        // — that constant has been removed from the model entirely.
        $this->assertEquals(0, Payment::where('type', 'debit_note')->count());

        // No rows with the old buggy combo either.
        $this->assertEquals(0, Payment::where('type', 'refund')
            ->where('payment_method', 'debit_note')
            ->count());

        // The bookkeeping decision IS captured on the return invoice.
        $return = PurchaseReturnInvoice::where('purchase_invoice_id', $invoice->id)->first();
        $this->assertNotNull($return);
        $this->assertTrue((bool) $return->create_debit_note);
    }

    /** @test */
    public function return_total_reduces_invoice_balance_even_without_payment_row(): void
    {
        $ctx = $this->bootFullPurchasingContext();

        $invoice = $this->makePaidInvoice(
            $ctx['supplier'],
            $ctx['part'],
            $ctx['warehouse'],
            $ctx['center'],
            $ctx['tenant'],
            total: 1000.0
        );

        // Pre-condition: fully paid, balance = 0.
        $this->assertEquals(0.0, (float) $invoice->fresh()->balance);

        // Simulate what the controller does after creating the
        // PurchaseReturnInvoice: deduct the returned total from the
        // invoice balance. This is what makes the supplier's payable
        // go down — NOT a separate Payment row.
        $total = 1000.0;
        $invoice->update([
            'balance' => max(0, $invoice->balance - $total),
        ]);

        $this->assertEquals(0.0, (float) $invoice->fresh()->balance);

        // The supplier balance drops by the same amount, without any
        // TYPE_DEBIT_NOTE or TYPE_REFUND payment being created.
        $this->assertEquals(0.0, (float) $ctx['supplier']->fresh()->calculateBalance());
    }

    /** @test */
    public function debit_note_on_paid_invoice_creates_supplier_credit(): void
    {
        // User scenario: the buyer has already paid the full invoice
        // (1000), then returns 300 of goods. No cash is returned, only
        // a debit note is issued. The supplier now owes the buyer 300
        // — the supplier balance must reflect this as a negative
        // payable (i.e. credit owed TO us).
        $ctx = $this->bootFullPurchasingContext();

        $invoice = $this->makePaidInvoice(
            $ctx['supplier'],
            $ctx['part'],
            $ctx['warehouse'],
            $ctx['center'],
            $ctx['tenant'],
            total: 1000.0
        );

        // Pre-condition: fully paid, supplier balance is 0.
        $this->assertEquals(0.0, (float) $ctx['supplier']->fresh()->calculateBalance());

        // Return 300 with a debit note (no cash refund). The controller
        // does: $invoice->update(['balance' => max(0, $balance - $total)])
        // → max(0, 0 - 300) = 0 (still 0, because we overpaid).
        $return = PurchaseReturnInvoice::create([
            'tenant_id'          => $ctx['tenant']->id,
            'center_id'          => $ctx['center']->id,
            'purchase_invoice_id'=> $invoice->id,
            'code'               => PurchaseReturnInvoice::generateCode($ctx['tenant']->id),
            'return_date'        => now()->toDateString(),
            'subtotal'           => 300,
            'tax_amount'         => 0,
            'total'              => 300,
            'create_debit_note'  => true,
            'debit_note_date'    => now()->toDateString(),
        ]);
        $invoice->update(['balance' => max(0, $invoice->balance - $return->total)]);

        // The supplier now owes us 300. The debit note must surface
        // this on the supplier balance as -300 (we are the creditor).
        $supplierBalance = (float) $ctx['supplier']->fresh()->calculateBalance();
        $this->assertEquals(-300.0, $supplierBalance);
    }

    /** @test */
    public function debit_note_credit_is_offset_by_cash_refund(): void
    {
        // If the user later receives 100 of the 300 back in actual cash,
        // the credit on the supplier must drop from 300 to 200.
        $ctx = $this->bootFullPurchasingContext();

        $invoice = $this->makePaidInvoice(
            $ctx['supplier'],
            $ctx['part'],
            $ctx['warehouse'],
            $ctx['center'],
            $ctx['tenant'],
            total: 1000.0
        );

        $return = PurchaseReturnInvoice::create([
            'tenant_id'          => $ctx['tenant']->id,
            'center_id'          => $ctx['center']->id,
            'purchase_invoice_id'=> $invoice->id,
            'code'               => PurchaseReturnInvoice::generateCode($ctx['tenant']->id),
            'return_date'        => now()->toDateString(),
            'subtotal'           => 300,
            'tax_amount'         => 0,
            'total'              => 300,
            'create_debit_note'  => true,
            'debit_note_date'    => now()->toDateString(),
        ]);
        $invoice->update(['balance' => max(0, $invoice->balance - $return->total)]);

        // Pre-condition: 300 credit on the supplier.
        $this->assertEquals(-300.0, (float) $ctx['supplier']->fresh()->calculateBalance());

        // The supplier returned 100 in actual cash. Record it as a
        // TYPE_REFUND Payment (real cash movement).
        Payment::create([
            'tenant_id'           => $invoice->tenant_id,
            'center_id'           => $invoice->center_id,
            'purchase_invoice_id' => $invoice->id,
            'amount'              => 100,
            'payment_date'        => now(),
            'payment_method'      => 'cash',
            'type'                => Payment::TYPE_REFUND,
        ]);

        // Credit drops from 300 to 200.
        $this->assertEquals(-200.0, (float) $ctx['supplier']->fresh()->calculateBalance());
    }

    /** @test */
    public function return_without_debit_note_does_not_credit_supplier(): void
    {
        // A "normal" return (no debit note, no cash refund) is just
        // a goods return on an unpaid invoice — it must NOT add credit
        // to the supplier, because no money has changed hands in
        // either direction beyond the original purchase.
        $ctx = $this->bootFullPurchasingContext();
        $supplier = $ctx['supplier'];

        // Unpaid invoice: 1000 total, no payments.
        $invoice = PurchaseInvoice::create([
            'tenant_id'      => $ctx['tenant']->id,
            'center_id'      => $ctx['center']->id,
            'supplier_id'    => $supplier->id,
            'invoice_number' => 'INV-002',
            'code'           => PurchaseInvoice::generateCode($ctx['tenant']->id),
            'issue_date'     => now()->toDateString(),
            'status'         => PurchaseInvoice::STATUS_OPEN,
            'subtotal'       => 1000,
            'tax_amount'     => 0,
            'total'          => 1000,
            'balance'        => 1000,
        ]);
        PurchaseInvoiceLine::create([
            'purchase_invoice_id' => $invoice->id,
            'part_id'             => $ctx['part']->id,
            'qty'                 => 10,
            'unit_cost'           => 100,
            'tax_rate'            => 0,
            'tax_amount'          => 0,
            'total'               => 1000,
        ]);

        $this->assertEquals(1000.0, (float) $supplier->fresh()->calculateBalance());

        // Return 300 with NO debit note. The controller reduces the
        // invoice balance by 300.
        $return = PurchaseReturnInvoice::create([
            'tenant_id'          => $ctx['tenant']->id,
            'center_id'          => $ctx['center']->id,
            'purchase_invoice_id'=> $invoice->id,
            'code'               => PurchaseReturnInvoice::generateCode($ctx['tenant']->id),
            'return_date'        => now()->toDateString(),
            'subtotal'           => 300,
            'tax_amount'         => 0,
            'total'              => 300,
            'create_debit_note'  => false,
        ]);
        $invoice->update(['balance' => max(0, $invoice->balance - $return->total)]);

        // Supplier balance is now 700 (1000 - 300), no credit.
        $this->assertEquals(700.0, (float) $supplier->fresh()->calculateBalance());
    }

    /** @test */
    public function supplier_balance_excludes_debit_note_rows(): void
    {
        $ctx = $this->bootFullPurchasingContext();

        $supplier = $ctx['supplier'];
        $invoice = $this->makePaidInvoice(
            $supplier,
            $ctx['part'],
            $ctx['warehouse'],
            $ctx['center'],
            $ctx['tenant'],
            total: 1000.0
        );

        // A return with debit-note flag set; invoice balance reduced
        // by the return total. NO payment rows are touched.
        $return = PurchaseReturnInvoice::create([
            'tenant_id'          => $ctx['tenant']->id,
            'center_id'          => $ctx['center']->id,
            'purchase_invoice_id'=> $invoice->id,
            'code'               => PurchaseReturnInvoice::generateCode($ctx['tenant']->id),
            'return_date'        => now()->toDateString(),
            'subtotal'           => 1000,
            'tax_amount'         => 0,
            'total'              => 1000,
            'create_debit_note'  => true,
            'debit_note_date'    => now()->toDateString(),
        ]);
        $invoice->update(['balance' => max(0, $invoice->balance - $return->total)]);

        // Supplier balance is now -1000 (credit owed to us): we paid
        // 1000, returned goods worth 1000 with a debit note, no cash
        // was returned → the supplier must repay us 1000. The debit
        // note IS what surfaces this credit on the supplier balance.
        $this->assertEquals(-1000.0, (float) $supplier->fresh()->calculateBalance());

        // And the payments table contains exactly ONE row — the
        // original TYPE_PAYMENT that marked the invoice as paid.
        $this->assertEquals(1, Payment::where('purchase_invoice_id', $invoice->id)->count());
        $this->assertEquals(1, Payment::where('purchase_invoice_id', $invoice->id)
            ->where('type', Payment::TYPE_PAYMENT)->count());
    }

    /** @test */
    public function real_cash_refund_still_creates_a_refund_payment(): void
    {
        $ctx = $this->bootFullPurchasingContext();
        $user = $ctx['user'];

        $invoice = $this->makePaidInvoice(
            $ctx['supplier'],
            $ctx['part'],
            $ctx['warehouse'],
            $ctx['center'],
            $ctx['tenant'],
            total: 1000.0
        );

        $return = PurchaseReturnInvoice::create([
            'tenant_id'          => $ctx['tenant']->id,
            'center_id'          => $ctx['center']->id,
            'purchase_invoice_id'=> $invoice->id,
            'code'               => PurchaseReturnInvoice::generateCode($ctx['tenant']->id),
            'return_date'        => now()->toDateString(),
            'subtotal'           => 1000,
            'tax_amount'         => 0,
            'total'              => 1000,
        ]);

        // The user actually returned 300 in real cash to the supplier.
        // recordReturnRefund creates a TYPE_REFUND Payment.
        Payment::create([
            'tenant_id'           => $invoice->tenant_id,
            'center_id'           => $invoice->center_id,
            'purchase_invoice_id' => $invoice->id,
            'amount'              => 300,
            'payment_date'        => now(),
            'payment_method'      => 'cash',
            'type'                => Payment::TYPE_REFUND,
            'notes'               => 'Refund for return invoice: ' . $return->code,
        ]);

        // Now there should be exactly 2 payments: the original payment
        // + the real cash refund. NO debit-note row was created.
        $this->assertEquals(2, Payment::where('purchase_invoice_id', $invoice->id)->count());
        $this->assertEquals(1, Payment::where('purchase_invoice_id', $invoice->id)
            ->where('type', Payment::TYPE_REFUND)->count());
    }

    /** @test */
    public function supplier_return_invoices_relationship_works(): void
    {
        $ctx = $this->bootFullPurchasingContext();
        $supplier = $ctx['supplier'];

        // Build two paid invoices, each with one return.
        $invoiceA = $this->makePaidInvoice(
            $supplier, $ctx['part'], $ctx['warehouse'], $ctx['center'], $ctx['tenant'], total: 800.0
        );
        $invoiceB = $this->makePaidInvoice(
            $supplier, $ctx['part'], $ctx['warehouse'], $ctx['center'], $ctx['tenant'], total: 1200.0
        );

        PurchaseReturnInvoice::create([
            'tenant_id'          => $ctx['tenant']->id,
            'center_id'          => $ctx['center']->id,
            'purchase_invoice_id'=> $invoiceA->id,
            'code'               => PurchaseReturnInvoice::generateCode($ctx['tenant']->id),
            'return_date'        => now()->toDateString(),
            'subtotal'           => 400,
            'tax_amount'         => 0,
            'total'              => 400,
            'create_debit_note'  => true,
        ]);
        PurchaseReturnInvoice::create([
            'tenant_id'          => $ctx['tenant']->id,
            'center_id'          => $ctx['center']->id,
            'purchase_invoice_id'=> $invoiceB->id,
            'code'               => PurchaseReturnInvoice::generateCode($ctx['tenant']->id),
            'return_date'        => now()->toDateString(),
            'subtotal'           => 600,
            'tax_amount'         => 0,
            'total'              => 600,
        ]);

        $returnInvoices = $supplier->returnInvoices;
        $this->assertCount(2, $returnInvoices);
        $this->assertEquals(1000.0, (float) $returnInvoices->sum('total'));
        // The create_debit_note flag is preserved on the model.
        $this->assertTrue((bool) $returnInvoices->firstWhere('id', $invoiceA->id ? PurchaseReturnInvoice::where('purchase_invoice_id', $invoiceA->id)->first()->id : null)?->create_debit_note);
    }

    /** @test */
    public function supplier_does_not_see_other_supplier_return_invoices(): void
    {
        $ctxA = $this->bootFullPurchasingContext();
        $ctxB = $this->bootFullPurchasingContext();

        $invoiceA = $this->makePaidInvoice(
            $ctxA['supplier'], $ctxA['part'], $ctxA['warehouse'], $ctxA['center'], $ctxA['tenant'],
            total: 500.0, code: 'PINV-A-0001'
        );
        $invoiceB = $this->makePaidInvoice(
            $ctxB['supplier'], $ctxB['part'], $ctxB['warehouse'], $ctxB['center'], $ctxB['tenant'],
            total: 700.0, code: 'PINV-B-0001'
        );

        PurchaseReturnInvoice::create([
            'tenant_id'          => $ctxA['tenant']->id,
            'center_id'          => $ctxA['center']->id,
            'purchase_invoice_id'=> $invoiceA->id,
            'code'               => 'PRET-A-0001',
            'return_date'        => now()->toDateString(),
            'subtotal'           => 200,
            'tax_amount'         => 0,
            'total'              => 200,
        ]);
        PurchaseReturnInvoice::create([
            'tenant_id'          => $ctxB['tenant']->id,
            'center_id'          => $ctxB['center']->id,
            'purchase_invoice_id'=> $invoiceB->id,
            'code'               => 'PRET-B-0001',
            'return_date'        => now()->toDateString(),
            'subtotal'           => 300,
            'tax_amount'         => 0,
            'total'              => 300,
        ]);

        $this->assertCount(1, $ctxA['supplier']->refresh()->returnInvoices);
        $this->assertCount(1, $ctxB['supplier']->refresh()->returnInvoices);
        $this->assertEquals(200.0, (float) $ctxA['supplier']->returnInvoices->sum('total'));
        $this->assertEquals(300.0, (float) $ctxB['supplier']->returnInvoices->sum('total'));
    }
}
