<?php

declare(strict_types=1);

namespace App\Services\Reports;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FinancialSummaryReportService
{
    /**
     * Compute full P&L financial statement breakdown for a single center.
     *
     * @return array<string, float>
     */
    public function computeCenterFinancialStatement(
        int $tenantId,
        int $centerId,
        ?Carbon $from,
        ?Carbon $to,
        bool $storeSalesOnly
    ): array {
        // 1. Sales Invoices Lines (Services vs Parts)
        $invServicesQuery = DB::table('invoice_lines')
            ->join('invoices', 'invoices.id', '=', 'invoice_lines.invoice_id')
            ->where('invoices.tenant_id', $tenantId)
            ->where('invoices.center_id', $centerId)
            ->where('invoices.type', 'invoice')
            ->whereNull('invoices.deleted_at')
            ->where('invoice_lines.is_part', 0);

        $invPartsQuery = DB::table('invoice_lines')
            ->join('invoices', 'invoices.id', '=', 'invoice_lines.invoice_id')
            ->where('invoices.tenant_id', $tenantId)
            ->where('invoices.center_id', $centerId)
            ->where('invoices.type', 'invoice')
            ->whereNull('invoices.deleted_at')
            ->where('invoice_lines.is_part', 1);

        if ($storeSalesOnly) {
            $invServicesQuery->whereNull('invoices.work_order_id');
            $invPartsQuery->whereNull('invoices.work_order_id');
        }

        if ($from) {
            $invServicesQuery->whereDate('invoices.issue_date', '>=', $from->toDateString());
            $invPartsQuery->whereDate('invoices.issue_date', '>=', $from->toDateString());
        }

        if ($to) {
            $invServicesQuery->whereDate('invoices.issue_date', '<=', $to->toDateString());
            $invPartsQuery->whereDate('invoices.issue_date', '<=', $to->toDateString());
        }

        $invServices = (array) $invServicesQuery->selectRaw('
            COALESCE(SUM(invoice_lines.qty * invoice_lines.unit_price), 0) as gross,
            COALESCE(SUM(invoice_lines.discount_amount), 0) as discount,
            COALESCE(SUM(invoice_lines.line_total_excl_tax), 0) as subtotal,
            COALESCE(SUM(invoice_lines.tax_amount), 0) as vat,
            COALESCE(SUM(invoice_lines.line_total_incl_tax), 0) as net
        ')->first();

        $invParts = (array) $invPartsQuery->selectRaw('
            COALESCE(SUM(invoice_lines.qty * invoice_lines.unit_price), 0) as gross,
            COALESCE(SUM(invoice_lines.discount_amount), 0) as discount,
            COALESCE(SUM(invoice_lines.line_total_excl_tax), 0) as subtotal,
            COALESCE(SUM(invoice_lines.tax_amount), 0) as vat,
            COALESCE(SUM(invoice_lines.line_total_incl_tax), 0) as net
        ')->first();

        // 2. Return Invoices Lines (Credit Notes)
        $retServicesQuery = DB::table('invoice_lines')
            ->join('invoices', 'invoices.id', '=', 'invoice_lines.invoice_id')
            ->where('invoices.tenant_id', $tenantId)
            ->where('invoices.center_id', $centerId)
            ->where('invoices.type', 'credit_note')
            ->whereNull('invoices.deleted_at')
            ->where('invoice_lines.is_part', 0);

        $retPartsQuery = DB::table('invoice_lines')
            ->join('invoices', 'invoices.id', '=', 'invoice_lines.invoice_id')
            ->where('invoices.tenant_id', $tenantId)
            ->where('invoices.center_id', $centerId)
            ->where('invoices.type', 'credit_note')
            ->whereNull('invoices.deleted_at')
            ->where('invoice_lines.is_part', 1);

        if ($storeSalesOnly) {
            $retServicesQuery->whereNull('invoices.work_order_id');
            $retPartsQuery->whereNull('invoices.work_order_id');
        }

        if ($from) {
            $retServicesQuery->whereDate('invoices.issue_date', '>=', $from->toDateString());
            $retPartsQuery->whereDate('invoices.issue_date', '>=', $from->toDateString());
        }

        if ($to) {
            $retServicesQuery->whereDate('invoices.issue_date', '<=', $to->toDateString());
            $retPartsQuery->whereDate('invoices.issue_date', '<=', $to->toDateString());
        }

        $retServices = (array) $retServicesQuery->selectRaw('
            COALESCE(SUM(invoice_lines.qty * invoice_lines.unit_price), 0) as gross,
            COALESCE(SUM(invoice_lines.discount_amount), 0) as discount,
            COALESCE(SUM(invoice_lines.line_total_excl_tax), 0) as subtotal,
            COALESCE(SUM(invoice_lines.tax_amount), 0) as vat,
            COALESCE(SUM(invoice_lines.line_total_incl_tax), 0) as net
        ')->first();

        $retParts = (array) $retPartsQuery->selectRaw('
            COALESCE(SUM(invoice_lines.qty * invoice_lines.unit_price), 0) as gross,
            COALESCE(SUM(invoice_lines.discount_amount), 0) as discount,
            COALESCE(SUM(invoice_lines.line_total_excl_tax), 0) as subtotal,
            COALESCE(SUM(invoice_lines.tax_amount), 0) as vat,
            COALESCE(SUM(invoice_lines.line_total_incl_tax), 0) as net
        ')->first();

        // 3. Purchases & Returns from Suppliers
        $purchQuery = DB::table('purchase_invoices')
            ->where('tenant_id', $tenantId)
            ->where('center_id', $centerId)
            ->whereNull('deleted_at');

        if ($from) {
            $purchQuery->where(function ($sub) use ($from) {
                $sub->whereDate('issue_date', '>=', $from->toDateString())
                    ->orWhere(function ($s2) use ($from) {
                        $s2->whereNull('issue_date')->whereDate('created_at', '>=', $from->toDateString());
                    });
            });
        }

        if ($to) {
            $purchQuery->where(function ($sub) use ($to) {
                $sub->whereDate('issue_date', '<=', $to->toDateString())
                    ->orWhere(function ($s2) use ($to) {
                        $s2->whereNull('issue_date')->whereDate('created_at', '<=', $to->toDateString());
                    });
            });
        }

        $purchases = (array) $purchQuery->selectRaw('
            COALESCE(SUM(subtotal + discount_amount), 0) as gross,
            COALESCE(SUM(discount_amount), 0) as discount,
            COALESCE(SUM(subtotal), 0) as subtotal,
            COALESCE(SUM(tax_amount), 0) as vat,
            COALESCE(SUM(total), 0) as net
        ')->first();

        $purchRetQuery = DB::table('purchase_return_invoices')
            ->where('tenant_id', $tenantId)
            ->where('center_id', $centerId)
            ->whereNull('deleted_at');

        if ($from) {
            $purchRetQuery->where(function ($sub) use ($from) {
                $sub->whereDate('return_date', '>=', $from->toDateString())
                    ->orWhere(function ($s2) use ($from) {
                        $s2->whereNull('return_date')->whereDate('created_at', '>=', $from->toDateString());
                    });
            });
        }

        if ($to) {
            $purchRetQuery->where(function ($sub) use ($to) {
                $sub->whereDate('return_date', '<=', $to->toDateString())
                    ->orWhere(function ($s2) use ($to) {
                        $s2->whereNull('return_date')->whereDate('created_at', '<=', $to->toDateString());
                    });
            });
        }

        $purchReturns = (array) $purchRetQuery->selectRaw('
            COALESCE(SUM(subtotal), 0) as gross,
            0 as discount,
            COALESCE(SUM(subtotal), 0) as subtotal,
            COALESCE(SUM(tax_amount), 0) as vat,
            COALESCE(SUM(total), 0) as net
        ')->first();

        // 4. Other Income & Expenses (Company Transactions)
        $incQuery = DB::table('company_transactions')
            ->where('tenant_id', $tenantId)
            ->where('center_id', $centerId)
            ->where('transaction_type', 'revenue')
            ->whereNull('deleted_at');

        $expQuery = DB::table('company_transactions')
            ->where('tenant_id', $tenantId)
            ->where('center_id', $centerId)
            ->where('transaction_type', 'expense')
            ->whereNull('deleted_at');

        if ($from) {
            $incQuery->where(function ($sub) use ($from) {
                $sub->whereDate('transaction_date', '>=', $from->toDateString())
                    ->orWhere(function ($s2) use ($from) {
                        $s2->whereNull('transaction_date')->whereDate('created_at', '>=', $from->toDateString());
                    });
            });
            $expQuery->where(function ($sub) use ($from) {
                $sub->whereDate('transaction_date', '>=', $from->toDateString())
                    ->orWhere(function ($s2) use ($from) {
                        $s2->whereNull('transaction_date')->whereDate('created_at', '>=', $from->toDateString());
                    });
            });
        }

        if ($to) {
            $incQuery->where(function ($sub) use ($to) {
                $sub->whereDate('transaction_date', '<=', $to->toDateString())
                    ->orWhere(function ($s2) use ($to) {
                        $s2->whereNull('transaction_date')->whereDate('created_at', '<=', $to->toDateString());
                    });
            });
            $expQuery->where(function ($sub) use ($to) {
                $sub->whereDate('transaction_date', '<=', $to->toDateString())
                    ->orWhere(function ($s2) use ($to) {
                        $s2->whereNull('transaction_date')->whereDate('created_at', '<=', $to->toDateString());
                    });
            });
        }

        $income = (array) $incQuery->selectRaw('
            COALESCE(SUM(amount), 0) as gross,
            0 as discount,
            COALESCE(SUM(amount), 0) as subtotal,
            COALESCE(SUM(tax_amount), 0) as vat,
            COALESCE(SUM(total_amount), 0) as net
        ')->first();

        $expense = (array) $expQuery->selectRaw('
            COALESCE(SUM(amount), 0) as gross,
            0 as discount,
            COALESCE(SUM(amount), 0) as subtotal,
            COALESCE(SUM(tax_amount), 0) as vat,
            COALESCE(SUM(total_amount), 0) as net
        ')->first();

        // 5. Salaries (Payroll & Other HR Payments)
        $payrollQuery = DB::table('hr_payrolls')
            ->where('tenant_id', $tenantId)
            ->where('center_id', $centerId)
            ->where('status', '!=', 'draft');

        $otherSalaryQuery = DB::table('hr_other_payments')
            ->where('tenant_id', $tenantId)
            ->where('center_id', $centerId)
            ->where('status', '!=', 'rejected')
            ->whereNull('deleted_at');

        if ($from) {
            $payrollQuery->where(function ($sub) use ($from) {
                $sub->whereDate('paid_at', '>=', $from->toDateString())
                    ->orWhere(function ($s2) use ($from) {
                        $s2->whereNull('paid_at')->whereDate('created_at', '>=', $from->toDateString());
                    });
            });
            $otherSalaryQuery->where(function ($sub) use ($from) {
                $sub->whereDate('payment_date', '>=', $from->toDateString())
                    ->orWhere(function ($s2) use ($from) {
                        $s2->whereNull('payment_date')->whereDate('created_at', '>=', $from->toDateString());
                    });
            });
        }

        if ($to) {
            $payrollQuery->where(function ($sub) use ($to) {
                $sub->whereDate('paid_at', '<=', $to->toDateString())
                    ->orWhere(function ($s2) use ($to) {
                        $s2->whereNull('paid_at')->whereDate('created_at', '<=', $to->toDateString());
                    });
            });
            $otherSalaryQuery->where(function ($sub) use ($to) {
                $sub->whereDate('payment_date', '<=', $to->toDateString())
                    ->orWhere(function ($s2) use ($to) {
                        $s2->whereNull('payment_date')->whereDate('created_at', '<=', $to->toDateString());
                    });
            });
        }

        $totalPayrollSalaryPayments = (float) ($payrollQuery->sum('net_total') ?? 0);
        $totalOtherSalaryPayments = (float) ($otherSalaryQuery->sum('amount') ?? 0);
        $totalSalary = $totalPayrollSalaryPayments + $totalOtherSalaryPayments;

        // 6. Bad Debt
        $badDebtQuery = DB::table('payments')
            ->where('tenant_id', $tenantId)
            ->where('center_id', $centerId)
            ->where('type', 'bad_debt')
            ->whereNull('deleted_at');

        if ($from) {
            $badDebtQuery->where(function ($sub) use ($from) {
                $sub->whereDate('payment_date', '>=', $from->toDateString())
                    ->orWhere(function ($s2) use ($from) {
                        $s2->whereNull('payment_date')->whereDate('created_at', '>=', $from->toDateString());
                    });
            });
        }

        if ($to) {
            $badDebtQuery->where(function ($sub) use ($to) {
                $sub->whereDate('payment_date', '<=', $to->toDateString())
                    ->orWhere(function ($s2) use ($to) {
                        $s2->whereNull('payment_date')->whereDate('created_at', '<=', $to->toDateString());
                    });
            });
        }

        $sumBadDebt = (float) ($badDebtQuery->sum('amount') ?? 0);

        // Compile totals
        $invSumGross = (float) ($invServices['gross'] ?? 0) + (float) ($invParts['gross'] ?? 0);
        $invSumDiscount = (float) ($invServices['discount'] ?? 0) + (float) ($invParts['discount'] ?? 0);
        $invSumSubtotal = (float) ($invServices['subtotal'] ?? 0) + (float) ($invParts['subtotal'] ?? 0);
        $invSumVat = (float) ($invServices['vat'] ?? 0) + (float) ($invParts['vat'] ?? 0);
        $invSumNet = (float) ($invServices['net'] ?? 0) + (float) ($invParts['net'] ?? 0);

        $retSumGross = (float) ($retServices['gross'] ?? 0) + (float) ($retParts['gross'] ?? 0);
        $retSumDiscount = (float) ($retServices['discount'] ?? 0) + (float) ($retParts['discount'] ?? 0);
        $retSumSubtotal = (float) ($retServices['subtotal'] ?? 0) + (float) ($retParts['subtotal'] ?? 0);
        $retSumVat = (float) ($retServices['vat'] ?? 0) + (float) ($retParts['vat'] ?? 0);
        $retSumNet = (float) ($retServices['net'] ?? 0) + (float) ($retParts['net'] ?? 0);

        $purchSumGross = (float) ($purchases['gross'] ?? 0) - (float) ($purchReturns['gross'] ?? 0);
        $purchSumDiscount = (float) ($purchases['discount'] ?? 0) - (float) ($purchReturns['discount'] ?? 0);
        $purchSumSubtotal = (float) ($purchases['subtotal'] ?? 0) - (float) ($purchReturns['subtotal'] ?? 0);
        $purchSumVat = (float) ($purchases['vat'] ?? 0) - (float) ($purchReturns['vat'] ?? 0);
        $purchSumNet = (float) ($purchases['net'] ?? 0) - (float) ($purchReturns['net'] ?? 0);

        $incExpGross = (float) ($income['gross'] ?? 0) - (float) ($expense['gross'] ?? 0);
        $incExpDiscount = (float) ($income['discount'] ?? 0) - (float) ($expense['discount'] ?? 0);
        $incExpSubtotal = (float) ($income['subtotal'] ?? 0) - (float) ($expense['subtotal'] ?? 0);
        $incExpVat = (float) ($income['vat'] ?? 0) - (float) ($expense['vat'] ?? 0);
        $incExpNet = (float) ($income['net'] ?? 0) - (float) ($expense['net'] ?? 0);

        // Sum of operations
        $sumGrossAmount = $invSumGross - $retSumGross - (float) ($purchases['gross'] ?? 0) + (float) ($purchReturns['gross'] ?? 0) + $incExpGross;
        $sumDiscountAmount = $invSumDiscount - $retSumDiscount - (float) ($purchases['discount'] ?? 0) + (float) ($purchReturns['discount'] ?? 0) + $incExpDiscount;
        $sumSubTotal = $invSumSubtotal - $retSumSubtotal - (float) ($purchases['subtotal'] ?? 0) + (float) ($purchReturns['subtotal'] ?? 0) + $incExpSubtotal;
        $sumVATAmount = $invSumVat - $retSumVat - (float) ($purchases['vat'] ?? 0) + (float) ($purchReturns['vat'] ?? 0) + $incExpVat;
        $sumNetAmount = $invSumNet - $retSumNet - (float) ($purchases['net'] ?? 0) + (float) ($purchReturns['net'] ?? 0) + $incExpNet;

        $sumFinalAmount = $sumNetAmount - $totalSalary - $sumBadDebt;
        $profit = $sumFinalAmount - $sumVATAmount;

        return [
            'invoicesServicesSumGrossAmount' => (float) ($invServices['gross'] ?? 0),
            'invoicesServicesSumDiscountAmount' => (float) ($invServices['discount'] ?? 0),
            'invoicesServicesSumSubTotal' => (float) ($invServices['subtotal'] ?? 0),
            'invoicesServicesSumVATAmount' => (float) ($invServices['vat'] ?? 0),
            'invoicesServicesSumNetAmount' => (float) ($invServices['net'] ?? 0),

            'invoicesSparePartsSumGrossAmount' => (float) ($invParts['gross'] ?? 0),
            'invoicesSparePartsSumDiscountAmount' => (float) ($invParts['discount'] ?? 0),
            'invoicesSparePartsSumSubTotal' => (float) ($invParts['subtotal'] ?? 0),
            'invoicesSparePartsSumVATAmount' => (float) ($invParts['vat'] ?? 0),
            'invoicesSparePartsSumNetAmount' => (float) ($invParts['net'] ?? 0),

            'invoicesSumGrossAmount' => $invSumGross,
            'invoicesSumDiscountAmount' => $invSumDiscount,
            'invoicesSumSubTotal' => $invSumSubtotal,
            'invoicesSumVATAmount' => $invSumVat,
            'invoicesSumNetAmount' => $invSumNet,

            'returnInvoicesServicesSumGrossAmount' => (float) ($retServices['gross'] ?? 0),
            'returnInvoicesServicesSumDiscountAmount' => (float) ($retServices['discount'] ?? 0),
            'returnInvoicesServicesSumSubTotal' => (float) ($retServices['subtotal'] ?? 0),
            'returnInvoicesServicesSumVATAmount' => (float) ($retServices['vat'] ?? 0),
            'returnInvoicesServicesSumNetAmount' => (float) ($retServices['net'] ?? 0),

            'returnInvoicesSparePartsSumGrossAmount' => (float) ($retParts['gross'] ?? 0),
            'returnInvoicesSparePartsSumDiscountAmount' => (float) ($retParts['discount'] ?? 0),
            'returnInvoicesSparePartsSumSubTotal' => (float) ($retParts['subtotal'] ?? 0),
            'returnInvoicesSparePartsSumVATAmount' => (float) ($retParts['vat'] ?? 0),
            'returnInvoicesSparePartsSumNetAmount' => (float) ($retParts['net'] ?? 0),

            'returnInvoicesSumGrossAmount' => $retSumGross,
            'returnInvoicesSumDiscountAmount' => $retSumDiscount,
            'returnInvoicesSumSubTotal' => $retSumSubtotal,
            'returnInvoicesSumVATAmount' => $retSumVat,
            'returnInvoicesSumNetAmount' => $retSumNet,

            'receivedSparePartsSumGrossAmount' => (float) ($purchases['gross'] ?? 0),
            'receivedSparePartsSumDiscountAmount' => (float) ($purchases['discount'] ?? 0),
            'receivedSparePartsSumSubTotal' => (float) ($purchases['subtotal'] ?? 0),
            'receivedSparePartsSumVATAmount' => (float) ($purchases['vat'] ?? 0),
            'receivedSparePartsSumNetAmount' => (float) ($purchases['net'] ?? 0),

            'returnedSparePartsSumGrossAmount' => (float) ($purchReturns['gross'] ?? 0),
            'returnedSparePartsSumDiscountAmount' => (float) ($purchReturns['discount'] ?? 0),
            'returnedSparePartsSumSubTotal' => (float) ($purchReturns['subtotal'] ?? 0),
            'returnedSparePartsSumVATAmount' => (float) ($purchReturns['vat'] ?? 0),
            'returnedSparePartsSumNetAmount' => (float) ($purchReturns['net'] ?? 0),

            'purchaseSparePartsSumGrossAmount' => $purchSumGross,
            'purchaseSparePartsSumDiscountAmount' => $purchSumDiscount,
            'purchaseSparePartsSumSubTotal' => $purchSumSubtotal,
            'purchaseSparePartsSumVATAmount' => $purchSumVat,
            'purchaseSparePartsSumNetAmount' => $purchSumNet,

            'incomeSumGrossAmount' => (float) ($income['gross'] ?? 0),
            'incomeSumDiscountAmount' => (float) ($income['discount'] ?? 0),
            'incomeSumSubTotal' => (float) ($income['subtotal'] ?? 0),
            'incomeSumVATAmount' => (float) ($income['vat'] ?? 0),
            'incomeSumNetAmount' => (float) ($income['net'] ?? 0),

            'expenseSumGrossAmount' => (float) ($expense['gross'] ?? 0),
            'expenseSumDiscountAmount' => (float) ($expense['discount'] ?? 0),
            'expenseSumSubTotal' => (float) ($expense['subtotal'] ?? 0),
            'expenseSumVATAmount' => (float) ($expense['vat'] ?? 0),
            'expenseSumNetAmount' => (float) ($expense['net'] ?? 0),

            'incomeExpenseSumGrossAmount' => $incExpGross,
            'incomeExpenseSumDiscountAmount' => $incExpDiscount,
            'incomeExpenseSumSubTotal' => $incExpSubtotal,
            'incomeExpenseSumVATAmount' => $incExpVat,
            'incomeExpenseSumNetAmount' => $incExpNet,

            'sumGrossAmount' => $sumGrossAmount,
            'sumDiscountAmount' => $sumDiscountAmount,
            'sumSubTotal' => $sumSubTotal,
            'sumVATAmount' => $sumVATAmount,
            'sumNetAmount' => $sumNetAmount,

            'totalPayrollSalaryPayments' => $totalPayrollSalaryPayments,
            'totalOtherSalaryPayments' => $totalOtherSalaryPayments,
            'totalSalary' => $totalSalary,
            'sumBadDebt' => $sumBadDebt,
            'sumFinalAmount' => $sumFinalAmount,
            'profit' => $profit,
        ];
    }
}
