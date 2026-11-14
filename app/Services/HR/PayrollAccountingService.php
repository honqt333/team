<?php

declare(strict_types=1);

namespace App\Services\HR;

use App\Models\CompanyTransaction;
use App\Models\HR\PayrollRun;
use App\Models\IncomeCategory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Payroll Accounting & Cash-Flow Posting Service.
 *
 * Accounting Policy (Option B — Cash-Flow Net Disbursement with Full Statutory Cost Breakdown):
 * - Primary payroll run posting (`postPayrollRunExpense`) records the actual net cash disbursed to employees
 *   (`net_salary`), while computing and documenting the full statutory breakdown (Gross Salary, Employee GOSI,
 *   Employer GOSI share, and Total Employer Cost) in the transaction notes and `calculatePayrollCostBreakdown()`.
 * - Callers requiring full employer accrual cost (`net_salary + employee_gosi + employer_gosi`) can pass
 *   `$useTotalEmployerCost = true`.
 */
class PayrollAccountingService
{
    public const EMPLOYER_GOSI_RATE_SAUDI = 11.75;

    public const EMPLOYER_GOSI_RATE_NON_SAUDI = 2.00;

    /**
     * Compute full payroll statutory cost breakdown including Employer GOSI share.
     *
     * @return array{
     *     total_base_salary: float,
     *     total_allowances: float,
     *     gross_salary: float,
     *     total_deductions: float,
     *     employee_gosi_amount: float,
     *     employer_gosi_amount: float,
     *     total_gosi_remittance: float,
     *     net_salary_disbursement: float,
     *     total_employer_cost: float
     * }
     */
    public function calculatePayrollCostBreakdown(PayrollRun $payrollRun): array
    {
        $items = $payrollRun->items()->with('employee.nationality')->get();

        $totalBase = round((float) $items->sum('base_salary'), 2);
        $totalAllowances = round((float) $items->sum('total_allowances'), 2);
        $grossSalary = round($totalBase + $totalAllowances, 2);

        $totalDeductions = round((float) $items->sum('total_deductions'), 2);
        $employeeGosi = round((float) $items->sum('gosi_amount'), 2);
        $netDisbursement = round((float) $items->sum('net_salary'), 2);

        $employerGosi = 0.0;

        foreach ($items as $item) {
            $employee = $item->employee;
            $baseForGosi = (float) $item->base_salary;

            if ($baseForGosi <= 0) {
                continue;
            }

            if ((float) $item->gosi_amount <= 0 && ($employee === null || (float) ($employee->gosi_rate ?? 0) <= 0)) {
                continue;
            }

            $natCode = strtoupper((string) ($employee?->nationality?->code ?? ''));
            $isSaudi = $natCode !== ''
                ? in_array($natCode, ['SA', 'SAU'], true)
                : ((float) ($employee?->gosi_rate ?? 0) >= 9.0);
            $employerRate = $isSaudi ? self::EMPLOYER_GOSI_RATE_SAUDI : self::EMPLOYER_GOSI_RATE_NON_SAUDI;
            $employerGosi += round($baseForGosi * ($employerRate / 100.0), 2);
        }

        $employerGosi = round($employerGosi, 2);
        $totalGosiRemittance = round($employeeGosi + $employerGosi, 2);
        $totalEmployerCost = round($netDisbursement + $employeeGosi + $employerGosi, 2);

        return [
            'total_base_salary' => $totalBase,
            'total_allowances' => $totalAllowances,
            'gross_salary' => $grossSalary,
            'total_deductions' => $totalDeductions,
            'employee_gosi_amount' => $employeeGosi,
            'employer_gosi_amount' => $employerGosi,
            'total_gosi_remittance' => $totalGosiRemittance,
            'net_salary_disbursement' => $netDisbursement,
            'total_employer_cost' => $totalEmployerCost,
        ];
    }

    /**
     * Post an approved/paid payroll run as an approved branch expense in CompanyTransaction.
     * Idempotent & race-safe: uses DB transaction + lockForUpdate() on PayrollRun.
     */
    public function postPayrollRunExpense(
        PayrollRun $payrollRun,
        ?Carbon $transactionDate = null,
        bool $useTotalEmployerCost = false
    ): CompanyTransaction {
        return DB::transaction(function () use ($payrollRun, $transactionDate, $useTotalEmployerCost): CompanyTransaction {
            /** @var PayrollRun $lockedRun */
            $lockedRun = PayrollRun::withoutGlobalScopes()
                ->where('tenant_id', $payrollRun->tenant_id)
                ->lockForUpdate()
                ->findOrFail($payrollRun->id);

            if ($lockedRun->company_transaction_id) {
                $existing = CompanyTransaction::withoutGlobalScopes()
                    ->where('tenant_id', $lockedRun->tenant_id)
                    ->find($lockedRun->company_transaction_id);

                if ($existing) {
                    if ($transactionDate && $existing->transaction_date?->toDateString() !== $transactionDate->toDateString()) {
                        $existing->update([
                            'transaction_date' => $transactionDate->toDateString(),
                            'updated_by' => auth()->id(),
                        ]);
                    }

                    $payrollRun->setRawAttributes($lockedRun->getRawOriginal(), true);

                    return $existing;
                }
            }

            $lockedRun->loadMissing(['items.employee.nationality', 'center']);
            $breakdown = $this->calculatePayrollCostBreakdown($lockedRun);

            $category = IncomeCategory::withoutGlobalScopes()->firstOrCreate(
                [
                    'tenant_id' => $lockedRun->tenant_id,
                    'transaction_type' => 'expense',
                    'name_en' => 'Payroll & Salaries',
                ],
                [
                    'name_ar' => 'الرواتب والأجور',
                    'is_taxable' => false,
                    'is_active' => true,
                    'updated_by' => auth()->id(),
                ]
            );

            $postedAmount = $useTotalEmployerCost
                ? $breakdown['total_employer_cost']
                : $breakdown['net_salary_disbursement'];

            $employeeCount = $lockedRun->items->count();

            $periodStart = $lockedRun->period_start?->format('Y-m-d') ?? (string) $lockedRun->period_start;
            $periodEnd = $lockedRun->period_end?->format('Y-m-d') ?? (string) $lockedRun->period_end;
            $effectiveDate = ($transactionDate ?? $lockedRun->payment_date ?? now())->toDateString();

            $title = __('hr.payroll.accounting_tx_title', [
                'period' => "{$periodStart} - {$periodEnd}",
            ]);

            $notes = __('hr.payroll.accounting_tx_notes', [
                'count' => $employeeCount,
                'gosi' => number_format($breakdown['employee_gosi_amount'], 2, '.', ''),
                'deductions' => number_format($breakdown['total_deductions'], 2, '.', ''),
            ]).sprintf(
                ' | Employer GOSI: %s | Total Employer Cost: %s',
                number_format($breakdown['employer_gosi_amount'], 2, '.', ''),
                number_format($breakdown['total_employer_cost'], 2, '.', '')
            );

            $transaction = CompanyTransaction::create([
                'tenant_id' => $lockedRun->tenant_id,
                'center_id' => $lockedRun->center_id,
                'title' => $title,
                'transaction_date' => $effectiveDate,
                'transaction_type' => 'expense',
                'income_category_id' => $category->id,
                'amount' => $postedAmount,
                'is_taxable' => false,
                'is_vat_inclusive' => false,
                'tax_amount' => 0,
                'total_amount' => $postedAmount,
                'notes' => $notes,
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now()->toDateString(),
                'updated_by' => auth()->id(),
            ]);

            $lockedRun->update([
                'company_transaction_id' => $transaction->id,
                'accounting_posted_at' => now(),
            ]);

            $payrollRun->setRawAttributes($lockedRun->getRawOriginal(), true);

            $lockedRun->logActivity('accounting_posted', [
                'company_transaction_id' => $transaction->id,
                'center_id' => $lockedRun->center_id,
                'amount' => $postedAmount,
                'employee_gosi_amount' => $breakdown['employee_gosi_amount'],
                'employer_gosi_amount' => $breakdown['employer_gosi_amount'],
                'total_employer_cost' => $breakdown['total_employer_cost'],
            ]);

            return $transaction;
        });
    }
}
