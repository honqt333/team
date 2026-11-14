<?php

declare(strict_types=1);

namespace App\Services\HR;

use App\Models\CompanyTransaction;
use App\Models\HR\PayrollRun;
use App\Models\IncomeCategory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PayrollAccountingService
{
    /**
     * Post an approved/paid payroll run as an approved branch expense in CompanyTransaction.
     * Idempotent: returns existing transaction if already posted.
     */
    public function postPayrollRunExpense(PayrollRun $payrollRun, ?Carbon $transactionDate = null): CompanyTransaction
    {
        if ($payrollRun->company_transaction_id) {
            $existing = CompanyTransaction::withoutGlobalScopes()
                ->where('tenant_id', $payrollRun->tenant_id)
                ->find($payrollRun->company_transaction_id);

            if ($existing) {
                if ($transactionDate && $existing->transaction_date?->toDateString() !== $transactionDate->toDateString()) {
                    $existing->update([
                        'transaction_date' => $transactionDate->toDateString(),
                        'updated_by' => auth()->id(),
                    ]);
                }

                return $existing;
            }
        }

        return DB::transaction(function () use ($payrollRun, $transactionDate): CompanyTransaction {
            $payrollRun->loadMissing(['items', 'center']);

            $category = IncomeCategory::withoutGlobalScopes()->firstOrCreate(
                [
                    'tenant_id' => $payrollRun->tenant_id,
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

            $totalNet = round((float) $payrollRun->items->sum('net_salary'), 2);
            $totalGosi = round((float) $payrollRun->items->sum('gosi_amount'), 2);
            $totalDeductions = round((float) $payrollRun->items->sum('total_deductions'), 2);
            $employeeCount = $payrollRun->items->count();

            $periodStart = $payrollRun->period_start?->format('Y-m-d') ?? (string) $payrollRun->period_start;
            $periodEnd = $payrollRun->period_end?->format('Y-m-d') ?? (string) $payrollRun->period_end;
            $effectiveDate = ($transactionDate ?? $payrollRun->payment_date ?? now())->toDateString();

            $title = __('hr.payroll.accounting_tx_title', [
                'period' => "{$periodStart} - {$periodEnd}",
            ]);

            $notes = __('hr.payroll.accounting_tx_notes', [
                'count' => $employeeCount,
                'gosi' => number_format($totalGosi, 2, '.', ''),
                'deductions' => number_format($totalDeductions, 2, '.', ''),
            ]);

            $transaction = CompanyTransaction::create([
                'tenant_id' => $payrollRun->tenant_id,
                'center_id' => $payrollRun->center_id,
                'title' => $title,
                'transaction_date' => $effectiveDate,
                'transaction_type' => 'expense',
                'income_category_id' => $category->id,
                'amount' => $totalNet,
                'is_taxable' => false,
                'is_vat_inclusive' => false,
                'tax_amount' => 0,
                'total_amount' => $totalNet,
                'notes' => $notes,
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now()->toDateString(),
                'updated_by' => auth()->id(),
            ]);

            $payrollRun->update([
                'company_transaction_id' => $transaction->id,
                'accounting_posted_at' => now(),
            ]);

            $payrollRun->logActivity('accounting_posted', [
                'company_transaction_id' => $transaction->id,
                'center_id' => $payrollRun->center_id,
                'amount' => $totalNet,
            ]);

            return $transaction;
        });
    }
}
