<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\CenterSequence;
use App\Models\CompanyTransaction;
use App\Models\Customer;
use App\Models\IncomeCategory;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\TenantTaxSetting;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * The invoice sequence year when the document crosses 31 December.
 *
 * THE QUESTION
 *
 * `InvoiceService::issueInvoice()` stamps the invoice with the issue
 * date it is given, but it picks the SEQUENCE YEAR from `now()`:
 *
 *     $year = now()->year;                                              // ← processing year
 *     $seq  = CenterSequence::getNextValue($t, $c, 'invoice', $year);
 *     $invoiceNumber = $tenant->formatDocumentCode('invoice', $c, $seq, $year);
 *
 * So a document approved on 20 December but processed on 3 January gets:
 *
 *     issue_date    = 2025-12-20     ← the business date
 *     invoice_number = INV-1-2026-000001   ← the PROCESSING year
 *
 * Every tax and reporting path in the system reads `issue_date` as the
 * document's date (`TaxPeriodService`, `FinancialSummaryReportService`),
 * so the document is attributed to 2025 while its number says 2026.
 *
 * WHAT THIS TEST DOES
 *
 * It drives the REAL transaction approval path with the clock frozen in
 * January and an approval date in the previous December, then reads the
 * SAVED invoice and the sequence counters back from the database.
 *
 * It does NOT assert what the answer should be — the rule is what is
 * under review. It RECORDS what happens, so the decision is made on
 * evidence. See the decision section of
 * `audit_reports/architecture_review/15_TAX_REFERENCE_AND_PATHS.md`.
 */
class InvoiceSequenceYearBoundaryTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Center $center;

    private User $user;

    private IncomeCategory $revenueCategory;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ([Permissions::INVOICES_VIEW, Permissions::INVOICES_CREATE] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $this->tenant = Tenant::factory()->create([
            // A format that shows the year EXPLICITLY, so the number can
            // be read against the date.
            'invoice_number_format' => 'INV-{CENTER}-{YYYY}-{SEQ}',
        ]);
        $this->center = Center::factory()->create(['tenant_id' => $this->tenant->id]);

        TenantTaxSetting::create([
            'tenant_id' => $this->tenant->id,
            'vat_enabled' => true,
            'vat_rate' => 15.00,
        ]);

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'current_center_id' => $this->center->id,
        ]);
        $this->user->centers()->attach($this->center->id, ['tenant_id' => $this->tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
        $this->user->givePermissionTo([Permissions::INVOICES_VIEW, Permissions::INVOICES_CREATE]);
        $this->user->refresh();

        $this->revenueCategory = IncomeCategory::create([
            'tenant_id' => $this->tenant->id,
            'name_ar' => 'خدمات عامة',
            'name_en' => 'General Services',
            'transaction_type' => 'revenue',
            'is_taxable' => true,
            'is_active' => true,
        ]);

        $this->actingAs($this->user);
    }

    private function draftTransaction(string $transactionDate): CompanyTransaction
    {
        $customer = Customer::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'name' => 'عميل سنة',
            'phone' => '056666'.uniqid(),
            'tax_number' => '322222222222223',
        ]);

        return CompanyTransaction::create([
            'tenant_id' => $this->tenant->id,
            'center_id' => $this->center->id,
            'title' => 'خدمة عبر العام',
            'transaction_date' => $transactionDate,
            'transaction_type' => 'revenue',
            'income_category_id' => $this->revenueCategory->id,
            'amount' => 200.00,
            'is_taxable' => true,
            'tax_amount' => 30.00,
            'total_amount' => 230.00,
            'contact_type' => 'customer',
            'contact_id' => $customer->id,
            'status' => 'draft',
            'updated_by' => $this->user->id,
        ]);
    }

    /**
     * The boundary case, through the real approval path.
     *
     * The clock is frozen on 3 January 2026. The transaction is dated
     * 18 December 2025 and approved on 20 December 2025 — both allowed by
     * the controller's validation (`after_or_equal:transaction_date`,
     * `before_or_equal:today`, where "today" is the frozen now).
     */
    public function test_a_document_approved_in_december_but_processed_in_january(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-03 09:30:00'));

        try {
            $tx = $this->draftTransaction('2025-12-18');

            $response = $this->post(route('settings.company.transactions.approve', $tx->id), [
                'approved_at' => '2025-12-20',
            ]);

            $response->assertRedirect();
            $response->assertSessionHasNoErrors();

            if ($tx->fresh()->status !== 'approved') {
                fwrite(STDERR, "\n[ERROR] status code = ".$response->getStatusCode()."\n");
                fwrite(STDERR, '[ERROR] flash error = '.json_encode($response->getSession()->get('error'))."\n");
                fwrite(STDERR, '[ERROR] errors = '.json_encode($response->getSession()->get('errors')?->toArray() ?? $response->getSession()->all())."\n");
            }

            $tx->refresh();
            $this->assertSame('approved', $tx->status);
            $this->assertSame('2025-12-20', $tx->approved_at?->format('Y-m-d'));

            $this->assertNotNull($tx->invoice_id);

            // ── Read the SAVED invoice back from the database ──
            $invoice = Invoice::findOrFail($tx->invoice_id);

            $issueDate = $invoice->issue_date?->format('Y-m-d');
            $number = (string) $invoice->invoice_number;

            fwrite(STDERR, "\n[BOUNDARY] issue_date       = {$issueDate}\n");
            fwrite(STDERR, "[BOUNDARY] invoice_number  = {$number}\n");
            fwrite(STDERR, '[BOUNDARY] processing year = '.Carbon::now()->year."\n");
            fwrite(STDERR, '[BOUNDARY] issue-date year = '.Carbon::parse($issueDate)->year."\n");

            // The invoice carries the APPROVAL date (proved by the
            // previous round) — that part is settled.
            $this->assertSame('2025-12-20', $issueDate);

            // The document is issued, not a draft.
            $this->assertSame('valid', $invoice->status);

            // ── The sequence counters that were actually consumed ──
            $sequences = CenterSequence::where('tenant_id', $this->tenant->id)
                ->where('center_id', $this->center->id)
                ->get(['type', 'year', 'current_value']);

            foreach ($sequences as $sequence) {
                fwrite(STDERR, sprintf(
                    "[BOUNDARY] sequence type=%s year=%s current_value=%d\n",
                    $sequence->type,
                    $sequence->year === null ? 'NULL' : $sequence->year,
                    $sequence->current_value
                ));
            }

            // The invoice sequence for the PROCESSING year was consumed.
            $invoiceSequence = $sequences->firstWhere('type', 'invoice');

            $this->assertNotNull(
                $invoiceSequence,
                'An invoice sequence must have been consumed for this tenant and centre.'
            );
            $this->assertSame(1, (int) $invoiceSequence->current_value);

            // ── THE RECORDED FACT ──
            // The number's year is the PROCESSING year; the document's
            // date is the APPROVAL year. Whether that is right is the
            // decision under review — this test records it.
            $numberYear = $this->yearIn($number);
            $issueYear = Carbon::parse($issueDate)->year;

            fwrite(STDERR, "[BOUNDARY] number year = {$numberYear} vs issue-date year = {$issueYear}\n");

            $this->assertSame(
                Carbon::now()->year,
                $numberYear,
                'RECORDED: the number carries the PROCESSING year.'
            );
            $this->assertSame(
                2025,
                $issueYear,
                'RECORDED: the document date carries the APPROVAL year.'
            );

            // The two disagree — which is precisely the finding.
            $this->assertNotSame(
                $issueYear,
                $numberYear,
                'RECORDED: the invoice number year and the invoice date year DISAGREE at the year boundary.'
            );
        } finally {
            Carbon::setTestNow();
        }
    }

    /**
     * A normal January invoice, to show the sequences stay sane and the
     * numbers do not repeat.
     */
    public function test_a_normal_january_invoice_keeps_the_sequences_sane(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-03 09:30:00'));

        try {
            $tx = $this->draftTransaction('2026-01-02');

            $response = $this->post(route('settings.company.transactions.approve', $tx->id), [
                'approved_at' => '2026-01-03',
            ]);

            $response->assertRedirect();
            $response->assertSessionHasNoErrors();

            $tx->refresh();
            $invoice = Invoice::findOrFail($tx->invoice_id);

            fwrite(STDERR, "\n[CONTROL] issue_date      = ".$invoice->issue_date?->format('Y-m-d')."\n");
            fwrite(STDERR, "[CONTROL] invoice_number = {$invoice->invoice_number}\n");

            $this->assertSame('2026-01-03', $invoice->issue_date?->format('Y-m-d'));
            $this->assertSame(2026, $this->yearIn((string) $invoice->invoice_number));

            // The SAME year's sequence is shared with the boundary
            // document — so the two January documents have consecutive
            // numbers and do not collide.
            $sequences = CenterSequence::where('tenant_id', $this->tenant->id)
                ->where('center_id', $this->center->id)
                ->where('type', 'invoice')
                ->get(['year', 'current_value']);

            foreach ($sequences as $sequence) {
                fwrite(STDERR, sprintf(
                    "[CONTROL] sequence year=%s current_value=%d\n",
                    $sequence->year === null ? 'NULL' : $sequence->year,
                    $sequence->current_value
                ));
            }
        } finally {
            Carbon::setTestNow();
        }
    }

    /**
     * Two documents in the same year must not share a number.
     */
    public function test_two_documents_in_the_same_year_do_not_share_a_number(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-03 09:30:00'));

        try {
            $numbers = [];

            foreach (['2026-01-02', '2026-01-03'] as $transactionDate) {
                $tx = $this->draftTransaction($transactionDate);

                $this->post(route('settings.company.transactions.approve', $tx->id), [
                    'approved_at' => $transactionDate,
                ])->assertRedirect();

                $tx->refresh();
                $numbers[] = (string) Invoice::findOrFail($tx->invoice_id)->invoice_number;
            }

            fwrite(STDERR, "\n[SEQUENCE] numbers = ".implode(' , ', $numbers)."\n");

            $this->assertCount(2, array_unique($numbers), 'Two documents must not share an invoice number.');
        } finally {
            Carbon::setTestNow();
        }
    }

    /**
     * The year that appears in the document number.
     */
    private function yearIn(string $number): int
    {
        if (preg_match('/(\d{4})/', $number, $m)) {
            return (int) $m[1];
        }

        $this->fail('The invoice number carries no four-digit year: '.$number);
    }
}
