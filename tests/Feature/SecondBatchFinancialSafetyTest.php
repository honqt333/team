<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Center;
use App\Models\CenterSequence;
use App\Models\Customer;
use App\Models\HR\Employee;
use App\Models\HR\PayrollItem;
use App\Models\HR\PayrollRun;
use App\Models\Invoice;
use App\Models\TaxPeriod;
use App\Models\Tenant;
use App\Models\User;
use App\Services\InvoiceService;
use App\Services\Reports\TaxPeriodService;
use App\Support\Permissions;
use DomainException;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SecondBatchFinancialSafetyTest extends TestCase
{
    use RefreshDatabase;

    private function actor(): User
    {
        $tenant = Tenant::factory()->create();
        $center = Center::factory()->create(['tenant_id' => $tenant->id]);
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'current_center_id' => $center->id]);
        $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);

        return $user;
    }

    private function invoice(User $user, ?int $centerId = null, string $type = 'invoice'): Invoice
    {
        return Invoice::factory()->create([
            'tenant_id' => $user->tenant_id, 'center_id' => $centerId ?? $user->current_center_id,
            'customer_id' => Customer::factory()->create(['tenant_id' => $user->tenant_id, 'center_id' => $centerId ?? $user->current_center_id])->id,
            'work_order_id' => null, 'status' => 'valid', 'type' => $type,
            'issue_date' => '2026-01-15', 'total_excl_tax' => 100, 'total_tax' => 15, 'total_incl_tax' => 115,
        ]);
    }

    public function test_stale_draft_cannot_change_issued_number_or_consume_another_sequence(): void
    {
        $user = $this->actor();
        $invoice = $this->invoice($user);
        $invoice->update(['status' => 'draft']);
        $stale = $invoice->fresh();
        $service = app(InvoiceService::class);
        $service->issueInvoice($invoice);
        $number = $invoice->invoice_number;
        $sequences = CenterSequence::pluck('current_value', 'id')->all();

        try {
            $service->issueInvoice($stale);
            $this->fail('Stale issuance accepted');
        } catch (Exception $e) {
            $this->assertSame(__('invoices.only_draft_can_be_issued'), $e->getMessage());
        }
        $this->assertSame($number, $invoice->fresh()->invoice_number);
        $this->assertSame($sequences, CenterSequence::pluck('current_value', 'id')->all());
    }

    public function test_period_is_consolidated_and_browser_amounts_are_ignored(): void
    {
        $user = $this->actor();
        $other = Center::factory()->create(['tenant_id' => $user->tenant_id]);
        $a = $this->invoice($user);
        $b = $this->invoice($user, $other->id);
        $outsider = $this->actor();
        $this->invoice($outsider);
        $this->actingAs($user);
        $service = app(TaxPeriodService::class);
        $period = $service->createPeriod($user->tenant_id, '2026-Q1', [['transaction_type' => 'sales_invoice', 'transaction_id' => $a->id, 'subtotal' => 999999, 'vat_amount' => 999999, 'is_selected' => true]], $user->id, $user->current_center_id);
        $this->assertNull($period->center_id);
        $this->assertEquals(30, $period->net_vat_amount);
        $this->assertCount(2, $period->transactions);
        $this->assertEqualsCanonicalizing([$user->current_center_id, $other->id], $period->transactions->pluck('center_id')->all());
        $this->assertEqualsCanonicalizing([$a->id, $b->id], $period->transactions->pluck('transaction_id')->all());
    }

    public function test_credit_note_is_negative_and_drafts_and_deleted_documents_are_excluded(): void
    {
        $user = $this->actor();
        $this->invoice($user);
        $this->invoice($user, null, 'credit_note');
        $this->invoice($user)->update(['status' => 'draft']);
        $this->invoice($user)->delete();
        $tx = app(TaxPeriodService::class)->collectTransactionsForPeriod($user->tenant_id, '2026-01-01', '2026-03-31');
        $this->assertCount(2, $tx);
        $this->assertEquals(0, array_sum(array_column($tx, 'vat_amount')));
        $this->assertContains('credit_note', array_column($tx, 'transaction_type'));
    }

    public function test_existing_legacy_period_is_preserved_and_cannot_be_posted_or_recreated(): void
    {
        $user = $this->actor();
        $period = TaxPeriod::create(['tenant_id' => $user->tenant_id, 'center_id' => $user->current_center_id, 'period_key' => '2026-Q1', 'name' => 'Legacy', 'year' => 2026, 'year_period' => '1', 'period_type' => 'quarterly', 'start_date' => '2026-01-01', 'end_date' => '2026-03-31', 'status' => 'draft']);
        $service = app(TaxPeriodService::class);

        foreach ([fn () => $service->createPeriod($user->tenant_id, '2026-Q1', [], $user->id), fn () => $service->postPeriod($period, $user->id), fn () => $service->recreatePeriod($period, $user->id)] as $attempt) {
            try {
                $attempt();
                $this->fail('Legacy mutation accepted');
            } catch (DomainException $e) {
                $this->assertNotEmpty($e->getMessage());
            }
        }
        $this->assertNull($period->fresh()->deleted_at);
        $this->assertSame('draft', $period->fresh()->status);
    }

    public function test_unknown_or_duplicate_transaction_identifiers_are_rejected(): void
    {
        $user = $this->actor();
        $invoice = $this->invoice($user);
        $service = app(TaxPeriodService::class);
        $tx = ['transaction_type' => 'sales_invoice', 'transaction_id' => $invoice->id, 'is_selected' => true];

        foreach ([[array_merge($tx, ['transaction_id' => 999999])], [$tx, $tx]] as $rows) {
            try {
                $service->createPeriod($user->tenant_id, '2026-Q1', $rows, $user->id);
                $this->fail('Invalid transaction accepted');
            } catch (DomainException $e) {
                $this->assertSame(__('reports.tax_periods_report.invalid_transactions'), $e->getMessage());
            }
        }
        $this->assertSame(0, TaxPeriod::count());
    }

    public function test_branch_invoice_permission_does_not_expose_consolidated_data(): void
    {
        $user = $this->actor();
        $user->givePermissionTo(Permissions::INVOICES_VIEW);
        $this->actingAs($user)->get('/app/reports/tax-periods')->assertForbidden();
        $user->givePermissionTo(Permissions::COMPANY_MANAGE);
        $this->get('/app/reports/tax-periods')->assertOk();
    }

    public function test_posted_period_cannot_be_recreated_through_stale_instance(): void
    {
        $user = $this->actor();
        $this->invoice($user);
        $service = app(TaxPeriodService::class);
        $period = $service->createPeriod($user->tenant_id, '2026-Q1', [], $user->id);
        $stale = $period->fresh();
        $service->postPeriod($period, $user->id);
        $this->expectException(DomainException::class);
        $service->recreatePeriod($stale, $user->id);
    }

    public function test_paid_payroll_item_cannot_be_deleted_through_another_draft_or_its_paid_parent(): void
    {
        $user = $this->actor();
        $user->givePermissionTo(Permissions::HR_PAYROLL_UPDATE);
        $employee = Employee::create(['tenant_id' => $user->tenant_id, 'center_id' => $user->current_center_id, 'name_ar' => 'Test', 'employee_number' => 'SECOND-BATCH']);
        $data = ['tenant_id' => $user->tenant_id, 'center_id' => $user->current_center_id, 'period_start' => '2026-01-01', 'period_end' => '2026-01-31'];
        $draft = PayrollRun::create($data + ['status' => 'draft']);
        $paid = PayrollRun::create($data + ['status' => 'paid']);
        $item = PayrollItem::create(['tenant_id' => $user->tenant_id, 'center_id' => $user->current_center_id, 'payroll_run_id' => $paid->id, 'employee_id' => $employee->id, 'base_salary' => 100, 'gosi_amount' => 0, 'net_salary' => 100]);
        $this->actingAs($user)->delete('/app/hr/payroll/'.$draft->id.'/items/'.$item->id)->assertNotFound();
        $this->delete('/app/hr/payroll/'.$paid->id.'/items/'.$item->id)->assertSessionHas('error');
        $this->assertDatabaseHas('hr_payroll_items', ['id' => $item->id]);
        $item->update(['payroll_run_id' => $draft->id]);
        $this->delete('/app/hr/payroll/'.$draft->id.'/items/'.$item->id)->assertSessionHas('success');
        $this->assertDatabaseMissing('hr_payroll_items', ['id' => $item->id]);
    }

    public function test_posting_rejects_a_transaction_already_selected_in_another_posted_period(): void
    {
        $user = $this->actor();
        $this->invoice($user);
        $service = app(TaxPeriodService::class);
        $period = $service->createPeriod($user->tenant_id, '2026-Q1', [], $user->id);
        $duplicate = $period->replicate();
        $duplicate->period_key = '2026-Q2';
        $duplicate->save();

        foreach ($period->transactions as $tx) {
            $copy = $tx->replicate();
            $copy->tax_period_id = $duplicate->id;
            $copy->save();
        }
        $service->postPeriod($period, $user->id);
        $this->expectException(DomainException::class);
        $service->postPeriod($duplicate, $user->id);
    }

    public function test_unselected_transactions_do_not_block_future_eligibility(): void
    {
        $user = $this->actor();
        $invoice = $this->invoice($user);
        $service = app(TaxPeriodService::class);
        $period = $service->createPeriod($user->tenant_id, '2026-Q1', [['transaction_type' => 'sales_invoice', 'transaction_id' => $invoice->id, 'is_selected' => false]], $user->id);
        $service->postPeriod($period, $user->id);
        $this->assertCount(1, $service->collectTransactionsForPeriod($user->tenant_id, '2026-01-01', '2026-03-31'));
        $this->assertEquals(0, $period->fresh()->net_vat_amount);
    }
}
