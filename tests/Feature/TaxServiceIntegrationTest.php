<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Services\CreditNoteService;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The tax behaviour of the PRODUCTION services, proved with documents that
 * actually exist in the database.
 *
 * WHY THIS TEST EXISTS
 *
 * The audit measured the three calculation paths by REPRODUCING their
 * arithmetic (`TaxProductionPathsComparisonTest`). That is a faithful
 * transcription, but it is still a transcription — it cannot show what the
 * system actually STORES.
 *
 * This test drives the real `InvoiceService` and the real
 * `CreditNoteService`, then READS THE SAVED ROWS BACK from the database and
 * records what is actually there.
 *
 * The rules it relies on:
 *
 *  - Nothing is planted by hand. The invoice's totals come from
 *    `InvoiceService::createFromWorkOrder()`, and the credit notes come
 *    from `CreditNoteService::createCreditNoteForInvoice()`.
 *  - The production formula is NOT copied into the test. The test only
 *    reads what the services wrote.
 *
 * The values printed by this test are the evidence for §6 of
 * `audit_reports/architecture_review/15_TAX_REFERENCE_AND_PATHS.md`.
 */
class TaxServiceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Center $center;

    private Customer $customer;

    private User $user;

    private Service $service;

    private Vehicle $vehicle;

    protected function setUp(): void
    {
        parent::setUp();

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

        $this->vehicle = Vehicle::factory()->create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'customer_id' => $this->customer->id,
        ]);

        $this->service = Service::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'name_ar' => 'خدمة اختبار',
            'name_en' => 'Test Service',
            'base_price' => 100,
            'type' => Service::TYPE_INTERNAL,
            'is_active' => true,
        ]);
    }

    /**
     * A work order with the given service lines.
     *
     * @param list<array{qty: float, unitPrice: float, discount: float, taxable: bool}> $lines
     */
    private function workOrderWith(array $lines, array $overrides = []): WorkOrder
    {
        $workOrder = WorkOrder::create(array_merge([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'customer_id' => $this->customer->id,
            'vehicle_id' => $this->vehicle->id,
            'code' => 'WO-TAX-'.uniqid(),
            'status' => WorkOrder::STATUS_DONE,
            'tax_enabled_snapshot' => true,
            'pricing_mode_snapshot' => 'exclusive',
            'tax_rate_snapshot' => 15,
            'currency_code' => 'SAR',
        ], $overrides));

        foreach ($lines as $line) {
            $net = $line['qty'] * $line['unitPrice'] - $line['discount'];

            WorkOrderItem::create([
                'work_order_id' => $workOrder->id,
                'service_id' => $this->service->id,
                'tenant_id' => $this->tenant->id,
                'center_id' => $this->center->id,
                'title' => 'بند '.$line['qty'],
                'qty' => $line['qty'],
                'unit_price' => $line['unitPrice'],
                'discount_amount' => $line['discount'],
                'final_unit_price' => $line['unitPrice'],
                'total' => $net,
                'status' => WorkOrderItem::STATUS_COMPLETED,
                'completed_at' => now(),
                'is_taxable' => $line['taxable'],
                'tax_category_code' => 'S',
                'tax_rate_snapshot' => 15,
            ]);
        }

        return $workOrder->fresh();
    }

    /**
     * The invoice that the PRODUCTION service produces, read back from
     * the database.
     *
     * @return array{header: array<string, mixed>, lines: list<array<string, mixed>>, model: Invoice}
     */
    private function issueFromWorkOrder(WorkOrder $workOrder): array
    {
        $invoice = app(InvoiceService::class)->createFromWorkOrder($workOrder, $this->user);
        app(InvoiceService::class)->issueInvoice($invoice);

        $saved = Invoice::findOrFail($invoice->id);

        $lines = DB::table('invoice_lines')
            ->where('invoice_id', $saved->id)
            ->orderBy('id')
            ->get([
                'id', 'description', 'qty', 'unit_price', 'discount_amount',
                'is_taxable', 'tax_rate_snapshot', 'tax_amount',
                'line_total_excl_tax', 'line_total_incl_tax',
            ])
            ->map(fn ($l) => (array) $l)
            ->all();

        return [
            'header' => [
                'status' => $saved->status,
                'total_excl_tax' => (float) $saved->total_excl_tax,
                'total_tax' => (float) $saved->total_tax,
                'total_incl_tax' => (float) $saved->total_incl_tax,
                'total_taxable_amount' => (float) $saved->total_taxable_amount,
            ],
            'lines' => $lines,
            'model' => $saved,
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // 1. A document created TODAY, through the real services
    // ─────────────────────────────────────────────────────────────

    /**
     * The awkward case: three lines of 33.3333.
     *
     * The quantity × unit price is 33.3333, which cannot be expressed in
     * two decimals — so this is where a halala appears.
     */
    public function test_an_awkward_document_records_what_the_service_actually_stores(): void
    {
        $workOrder = $this->workOrderWith([
            ['qty' => 1.0, 'unitPrice' => 33.3333, 'discount' => 0.0, 'taxable' => true],
            ['qty' => 1.0, 'unitPrice' => 33.3333, 'discount' => 0.0, 'taxable' => true],
            ['qty' => 1.0, 'unitPrice' => 33.3333, 'discount' => 0.0, 'taxable' => true],
        ]);

        $result = $this->issueFromWorkOrder($workOrder);

        $header = $result['header'];

        fwrite(STDERR, "\n[AWKWARD] header: ".json_encode($header)."\n");

        foreach ($result['lines'] as $line) {
            fwrite(STDERR, '[AWKWARD] line: '.json_encode([
                'qty' => $line['qty'],
                'unit_price' => $line['unit_price'],
                'excl' => $line['line_total_excl_tax'],
                'tax' => $line['tax_amount'],
                'incl' => $line['line_total_incl_tax'],
            ])."\n");
        }

        // The sum of the STORED line values, as they sit in the database.
        $sumExcl = array_sum(array_map(fn ($l) => (float) $l['line_total_excl_tax'], $result['lines']));
        $sumTax = array_sum(array_map(fn ($l) => (float) $l['tax_amount'], $result['lines']));
        $sumIncl = array_sum(array_map(fn ($l) => (float) $l['line_total_incl_tax'], $result['lines']));

        fwrite(STDERR, sprintf(
            "[AWKWARD] stored line sum: excl %.2f tax %.2f incl %.2f\n",
            $sumExcl,
            $sumTax,
            $sumIncl
        ));

        // The header is computed FROM the stored lines, so it agrees with
        // them — the document is internally consistent.
        $this->assertEqualsWithDelta($sumExcl, $header['total_excl_tax'], 0.001);
        $this->assertEqualsWithDelta($sumTax, $header['total_tax'], 0.001);
        $this->assertEqualsWithDelta($sumIncl, $header['total_incl_tax'], 0.001);

        // RECORDED: the real sum of three 33.3333 lines is 99.9999,
        // which rounds to 100.00 — but the stored lines carry 33.33
        // each, so the document says 99.99.
        $this->assertSame(99.99, $header['total_excl_tax']);
        $this->assertSame(114.99, $header['total_incl_tax']);
        $this->assertSame(15.0, $header['total_tax']);
    }

    /**
     * The same document read through the credit-note path.
     */
    public function test_sequential_partial_credit_notes_against_an_awkward_document(): void
    {
        $workOrder = $this->workOrderWith([
            ['qty' => 1.0, 'unitPrice' => 33.3333, 'discount' => 0.0, 'taxable' => true],
            ['qty' => 1.0, 'unitPrice' => 33.3333, 'discount' => 0.0, 'taxable' => true],
            ['qty' => 1.0, 'unitPrice' => 33.3333, 'discount' => 0.0, 'taxable' => true],
        ]);

        $result = $this->issueFromWorkOrder($workOrder);
        $invoice = $result['model'];

        $original = [
            'excl' => (float) $invoice->total_excl_tax,
            'tax' => (float) $invoice->total_tax,
            'incl' => (float) $invoice->total_incl_tax,
        ];

        fwrite(STDERR, "\n[RECONCILE] original invoice: ".json_encode($original)."\n");

        $service = app(CreditNoteService::class);

        // Return ONE line at a time, until the returnable amount is
        // exhausted. The summary decides what is returnable.
        $cumulative = ['excl' => 0.0, 'tax' => 0.0, 'incl' => 0.0];
        $notes = [];

        for ($round = 1; $round <= 5; $round++) {
            $summary = $service->getReturnableSummaryForInvoice($invoice->fresh());

            $returnable = collect($summary['services'])->sum('refundable_amount');

            fwrite(STDERR, sprintf(
                "[RECONCILE] round %d: remaining refundable = %.2f\n",
                $round,
                $returnable
            ));

            if ($returnable <= 0.001) {
                break;
            }

            // Take the first service that still has something to return.
            $target = collect($summary['services'])->first(fn ($s) => $s['refundable_amount'] > 0.001);

            if (! $target) {
                break;
            }

            $creditNote = $service->createCreditNoteForInvoice($invoice->fresh(), [
                'return_reason' => 'اختبار التسوية',
                'services' => [
                    ['id' => $target['id'], 'return_amount' => $target['refundable_amount']],
                ],
            ], $this->user);

            // READ THE SAVED CREDIT NOTE BACK.
            $saved = Invoice::findOrFail($creditNote->id);
            $savedLines = DB::table('invoice_lines')
                ->where('invoice_id', $saved->id)
                ->orderBy('id')
                ->get(['tax_amount', 'line_total_excl_tax', 'line_total_incl_tax'])
                ->map(fn ($l) => (array) $l)
                ->all();

            $note = [
                'excl' => (float) $saved->total_excl_tax,
                'tax' => (float) $saved->total_tax,
                'incl' => (float) $saved->total_incl_tax,
            ];

            $notes[] = $note;

            $cumulative['excl'] = round($cumulative['excl'] + $note['excl'], 2);
            $cumulative['tax'] = round($cumulative['tax'] + $note['tax'], 2);
            $cumulative['incl'] = round($cumulative['incl'] + $note['incl'], 2);

            fwrite(STDERR, sprintf(
                "[RECONCILE] credit note #%d saved: excl %.2f tax %.2f incl %.2f  | cumulative: excl %.2f tax %.2f incl %.2f\n",
                $round,
                $note['excl'],
                $note['tax'],
                $note['incl'],
                $cumulative['excl'],
                $cumulative['tax'],
                $cumulative['incl']
            ));

            foreach ($savedLines as $line) {
                fwrite(STDERR, sprintf(
                    "            line: excl %.2f tax %.2f incl %.2f\n",
                    $line['line_total_excl_tax'],
                    $line['tax_amount'],
                    $line['line_total_incl_tax']
                ));
            }
        }

        fwrite(STDERR, sprintf(
            "[RECONCILE] FINAL: original %.2f/%.2f/%.2f  credits %.2f/%.2f/%.2f  REMAINING %.2f/%.2f/%.2f\n",
            $original['excl'],
            $original['tax'],
            $original['incl'],
            $cumulative['excl'],
            $cumulative['tax'],
            $cumulative['incl'],
            round($original['excl'] - $cumulative['excl'], 2),
            round($original['tax'] - $cumulative['tax'], 2),
            round($original['incl'] - $cumulative['incl'], 2)
        ));

        $this->assertNotEmpty($notes, 'At least one credit note must have been created.');
    }

    // ─────────────────────────────────────────────────────────────
    // 2. A work order that mixes a taxable and an exempt line
    // ─────────────────────────────────────────────────────────────

    /**
     * The mixed work order, compared with the invoice it produces.
     */
    public function test_a_mixed_taxable_and_exempt_work_order_against_its_invoice(): void
    {
        $workOrder = $this->workOrderWith([
            ['qty' => 1.0, 'unitPrice' => 100.0, 'discount' => 0.0, 'taxable' => true],
            ['qty' => 1.0, 'unitPrice' => 80.0, 'discount' => 0.0, 'taxable' => false],
        ]);

        // What the WORK ORDER says.
        $workOrder->recalculateTotals();
        $workOrder = $workOrder->fresh();

        $workOrderTotals = [
            'excl' => (float) $workOrder->total_excl_tax,
            'tax' => (float) $workOrder->total_tax,
            'incl' => (float) $workOrder->total_incl_tax,
        ];

        // What the INVOICE says.
        $result = $this->issueFromWorkOrder($workOrder);
        $invoiceTotals = $result['header'];

        fwrite(STDERR, "\n[MIXED] work order : ".json_encode($workOrderTotals)."\n");
        fwrite(STDERR, '[MIXED] invoice     : '.json_encode([
            'excl' => $invoiceTotals['total_excl_tax'],
            'tax' => $invoiceTotals['total_tax'],
            'incl' => $invoiceTotals['total_incl_tax'],
        ])."\n");

        foreach ($result['lines'] as $line) {
            fwrite(STDERR, sprintf(
                "[MIXED] invoice line: excl %.2f tax %.2f incl %.2f taxable=%s\n",
                $line['line_total_excl_tax'],
                $line['tax_amount'],
                $line['line_total_incl_tax'],
                $line['is_taxable'] ? 'yes' : 'no'
            ));
        }

        // RECORDED: the work order applies ONE order-level tax flag to
        // the whole aggregated net, so the exempt line is taxed too.
        $this->assertSame(180.0, $workOrderTotals['excl']);
        $this->assertSame(27.0, $workOrderTotals['tax']);
        $this->assertSame(207.0, $workOrderTotals['incl']);

        // RECORDED: the invoice stores the line as EXEMPT
        // (`is_taxable = no`) and nevertheless taxes it at 15%.
        //
        // `InvoiceService::createFromWorkOrder()` WRITES `is_taxable`
        // (`:171`) but never READS it when computing the tax — the
        // calculation only looks at the order-level `tax_enabled_snapshot`
        // and the line's `tax_rate_snapshot`. So a line that is marked
        // exempt is still taxed.
        $this->assertSame(180.0, $invoiceTotals['total_excl_tax']);
        $this->assertSame(27.0, $invoiceTotals['total_tax']);
        $this->assertSame(207.0, $invoiceTotals['total_incl_tax']);

        // The exempt line is marked exempt AND taxed.
        $exemptLine = null;

        foreach ($result['lines'] as $line) {
            if (! $line['is_taxable']) {
                $exemptLine = $line;
            }
        }

        $this->assertNotNull($exemptLine, 'The exempt line must be present.');
        $this->assertSame(
            12.0,
            (float) $exemptLine['tax_amount'],
            'RECORDED: the line stored as NOT taxable still carries 12.00 of tax.'
        );

        // So the two documents of the same sale AGREE — both tax the
        // exempt line. The divergence is not between the two paths; it
        // is between the DATA (the line says exempt) and BOTH paths.
        $this->assertSame(
            $workOrderTotals['incl'],
            $invoiceTotals['total_incl_tax'],
            'RECORDED: the work order and its invoice agree — both tax the exempt line.'
        );
    }
}
