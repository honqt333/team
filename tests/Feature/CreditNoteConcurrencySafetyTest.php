<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Part;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\CreditNoteService;
use App\Services\PaymentService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CreditNoteConcurrencySafetyTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenantA;

    private Tenant $tenantB;

    private Center $centerA1;

    private Center $centerA2;

    private Center $centerB1;

    private User $userA;

    private Customer $customerA1;

    private Warehouse $warehouseA1;

    private Part $partA1;

    private CreditNoteService $creditNoteService;

    private PaymentService $paymentService;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Carbon::setTestNow(Carbon::parse('2026-11-15 12:00:00', 'UTC'));

        $this->tenantA = Tenant::factory()->create(['status' => 'active', 'country' => 'SA']);
        $this->tenantB = Tenant::factory()->create(['status' => 'active', 'country' => 'SA']);

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

        $this->warehouseA1 = Warehouse::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA1->id,
        ]);

        $this->partA1 = Part::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'default_sale_price' => 100.00,
        ]);

        $this->actingAs($this->userA);
        $this->creditNoteService = app(CreditNoteService::class);
        $this->paymentService = app(PaymentService::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * @return array{invoice: Invoice, serviceLine: InvoiceLine, partLine: InvoiceLine}
     */
    private function createDirectInvoiceWithLines(): array
    {
        $invoice = Invoice::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA1->id,
            'customer_id' => $this->customerA1->id,
            'work_order_id' => null,
            'type' => 'invoice',
            'status' => 'valid',
            'payment_status' => 'unpaid',
            'issue_date' => now(),
            'tax_enabled_snapshot' => false,
            'pricing_mode_snapshot' => 'exclusive',
            'tax_rate_snapshot' => 0.00,
            'total_excl_tax' => 1000.00,
            'total_tax' => 0.00,
            'total_incl_tax' => 1000.00,
            'total_paid' => 0.00,
        ]);

        $serviceLine = InvoiceLine::create([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA1->id,
            'invoice_id' => $invoice->id,
            'is_part' => false,
            'description' => 'Service Line',
            'qty' => 1,
            'unit_price' => 600.00,
            'discount_amount' => 0.00,
            'is_taxable' => false,
            'tax_category_code' => 'E',
            'tax_rate_snapshot' => 0.00,
            'tax_amount' => 0.00,
            'line_total_excl_tax' => 600.00,
            'line_total_incl_tax' => 600.00,
        ]);

        $partLine = InvoiceLine::create([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA1->id,
            'invoice_id' => $invoice->id,
            'is_part' => true,
            'part_id' => $this->partA1->id,
            'description' => 'Part Line',
            'qty' => 4,
            'unit_price' => 100.00,
            'discount_amount' => 0.00,
            'is_taxable' => false,
            'tax_category_code' => 'E',
            'tax_rate_snapshot' => 0.00,
            'tax_amount' => 0.00,
            'line_total_excl_tax' => 400.00,
            'line_total_incl_tax' => 400.00,
        ]);

        return [
            'invoice' => $invoice,
            'serviceLine' => $serviceLine,
            'partLine' => $partLine,
        ];
    }

    public function test_two_partial_credit_notes_and_over_return_prevention(): void
    {
        ['invoice' => $invoice, 'serviceLine' => $serviceLine, 'partLine' => $partLine] = $this->createDirectInvoiceWithLines();

        // First partial credit note: 300 on service, 2 qty (200) on part => 500 total
        $cn1 = $this->creditNoteService->createCreditNoteForInvoice($invoice, [
            'services' => [['id' => $serviceLine->id, 'return_amount' => 300.00]],
            'parts' => [['id' => $partLine->id, 'return_qty' => 2, 'return_to_stock' => false]],
        ], $this->userA);

        $this->assertEquals(500.00, (float) $cn1->total_incl_tax);

        // Second partial credit note: remaining 300 on service, 2 qty (200) on part => 500 total
        $cn2 = $this->creditNoteService->createCreditNoteForInvoice($invoice, [
            'services' => [['id' => $serviceLine->id, 'return_amount' => 300.00]],
            'parts' => [['id' => $partLine->id, 'return_qty' => 2, 'return_to_stock' => false]],
        ], $this->userA);

        $this->assertEquals(500.00, (float) $cn2->total_incl_tax);

        $invoice->refresh();
        $this->assertSame('paid', $invoice->payment_status);
        $this->assertEquals(0.00, (float) $invoice->balance);

        // Attempting to return already-returned quantity or amount must be rejected
        $this->expectException(InvalidArgumentException::class);
        $this->creditNoteService->createCreditNoteForInvoice($invoice, [
            'parts' => [['id' => $partLine->id, 'return_qty' => 1, 'return_to_stock' => false]],
        ], $this->userA);
    }

    public function test_exceeding_remaining_amount_is_rejected_and_balance_remains_unchanged(): void
    {
        ['invoice' => $invoice, 'serviceLine' => $serviceLine] = $this->createDirectInvoiceWithLines();

        $this->creditNoteService->createCreditNoteForInvoice($invoice, [
            'services' => [['id' => $serviceLine->id, 'return_amount' => 400.00]],
        ], $this->userA);

        $invoice->refresh();
        $this->assertEquals(600.00, (float) $invoice->balance);
        $this->assertSame('partial', $invoice->payment_status);

        // Try to return 250 when only 200 remains on service line
        $rejected = false;

        try {
            $this->creditNoteService->createCreditNoteForInvoice($invoice, [
                'services' => [['id' => $serviceLine->id, 'return_amount' => 250.00]],
            ], $this->userA);
        } catch (InvalidArgumentException $e) {
            $rejected = true;
        }

        $this->assertTrue($rejected);
        $invoice->refresh();
        $this->assertEquals(600.00, (float) $invoice->balance);
        $this->assertSame('partial', $invoice->payment_status);
        $this->assertSame(1, Invoice::where('original_invoice_id', $invoice->id)->where('type', 'credit_note')->count());
    }

    public function test_failed_transaction_leaves_no_partial_credit_note_or_lines(): void
    {
        ['invoice' => $invoice, 'serviceLine' => $serviceLine] = $this->createDirectInvoiceWithLines();

        // Fail inside DB::transaction() because refund_payment exceeds net_refund_due (0 since invoice is unpaid)
        $failed = false;

        try {
            $this->creditNoteService->createCreditNoteForInvoice($invoice, [
                'services' => [['id' => $serviceLine->id, 'return_amount' => 300.00]],
                'refund_payment' => ['amount' => 100.00, 'payment_method' => 'cash'],
            ], $this->userA);
        } catch (InvalidArgumentException $e) {
            $failed = true;
        }

        $this->assertTrue($failed);
        $this->assertSame(0, Invoice::where('original_invoice_id', $invoice->id)->where('type', 'credit_note')->count());
        $this->assertSame(2, InvoiceLine::where('invoice_id', $invoice->id)->count());

        $invoice->refresh();
        $this->assertSame('unpaid', $invoice->payment_status);
        $this->assertEquals(1000.00, (float) $invoice->balance);
    }

    public function test_stale_invoice_copy_re_reads_locked_payment_before_computing_debt_offset(): void
    {
        ['invoice' => $invoice, 'serviceLine' => $serviceLine] = $this->createDirectInvoiceWithLines();

        // Hold a stale in-memory copy of the invoice before payment is recorded
        $staleInvoice = Invoice::findOrFail($invoice->id);
        $this->assertEquals(0.00, (float) $staleInvoice->total_paid);

        // Concurrent/prior collector records 800 payment
        $this->paymentService->recordPayment($invoice, ['amount' => 800.00, 'payment_method' => 'cash']);

        // Now credit note for 500 is issued using the stale in-memory invoice object
        // Remaining debt is 200, so debt_offset MUST be 200 and net_refund_due MUST be 300!
        $cn = $this->creditNoteService->createCreditNoteForInvoice($staleInvoice, [
            'services' => [['id' => $serviceLine->id, 'return_amount' => 500.00]],
        ], $this->userA);

        $this->assertEquals(200.00, (float) $cn->debt_offset);
        $this->assertEquals(300.00, (float) $cn->net_refund_due);

        $invoice->refresh();
        $this->assertSame('paid', $invoice->payment_status);
        $this->assertEquals(800.00, (float) $invoice->total_paid);
        $this->assertEquals(0.00, (float) $invoice->balance);
    }

    public function test_credit_note_then_payment_preserves_correct_balance_and_blocks_overpayment(): void
    {
        ['invoice' => $invoice, 'serviceLine' => $serviceLine] = $this->createDirectInvoiceWithLines();

        $this->creditNoteService->createCreditNoteForInvoice($invoice, [
            'services' => [['id' => $serviceLine->id, 'return_amount' => 350.00]],
        ], $this->userA);

        $invoice->refresh();
        $this->assertEquals(650.00, (float) $invoice->balance);
        $this->assertSame('partial', $invoice->payment_status);

        $this->paymentService->recordPayment($invoice, ['amount' => 650.00, 'payment_method' => 'cash']);
        $invoice->refresh();
        $this->assertSame('paid', $invoice->payment_status);
        $this->assertEquals(650.00, (float) $invoice->total_paid);
        $this->assertEquals(0.00, (float) $invoice->balance);
    }

    public function test_cross_center_and_cross_tenant_credit_notes_do_not_affect_returnable_summary_or_balance(): void
    {
        ['invoice' => $invoice, 'serviceLine' => $serviceLine] = $this->createDirectInvoiceWithLines();

        // Cross-center and cross-tenant credit notes referencing the same ID
        Invoice::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'center_id' => $this->centerA2->id,
            'customer_id' => $this->customerA1->id,
            'original_invoice_id' => $invoice->id,
            'type' => 'credit_note',
            'status' => 'valid',
            'issue_date' => now(),
            'total_incl_tax' => 500.00,
        ]);
        Invoice::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'center_id' => $this->centerB1->id,
            'customer_id' => $this->customerA1->id,
            'original_invoice_id' => $invoice->id,
            'type' => 'credit_note',
            'status' => 'valid',
            'issue_date' => now(),
            'total_incl_tax' => 500.00,
        ]);

        $summary = $this->creditNoteService->getReturnableSummaryForInvoice($invoice);
        $this->assertEquals(1000.00, (float) $summary['original_invoice']['remaining']);
        $this->assertEquals(1000.00, (float) $invoice->fresh()->balance);
    }
}
