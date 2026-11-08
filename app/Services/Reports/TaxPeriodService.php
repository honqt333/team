<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Models\CompanyTransaction;
use App\Models\Invoice;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturnInvoice;
use App\Models\TaxPeriod;
use Carbon\Carbon;
use DomainException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TaxPeriodService
{
    /**
     * Get list of quarterly periods for the last 3 years (e.g., current year, -1, -2).
     */
    public function get3YearsPeriods(int $tenantId, ?int $centerId = null): array
    {
        $currentYear = (int) date('Y');
        $years = [$currentYear, $currentYear - 1, $currentYear - 2];

        $periodsQuery = TaxPeriod::withoutGlobalScopes()
            ->where('tenant_id', $tenantId);

        if ($centerId) {
            $periodsQuery->where(function ($q) use ($centerId) {
                $q->where('center_id', $centerId)->orWhereNull('center_id');
            });
        }

        $existingPeriods = $periodsQuery->get()->keyBy('period_key');

        $result = [];

        foreach ($years as $year) {
            $yearQuarters = [];

            for ($q = 1; $q <= 4; $q++) {
                $key = "{$year}-Q{$q}";
                $dates = $this->getQuarterDates($year, $q);
                $existing = $existingPeriods->get($key);

                $yearQuarters[] = [
                    'key' => $key,
                    'year' => $year,
                    'quarter' => $q,
                    'name_ar' => "الربع {$this->getQuarterArabicName($q)} {$year}",
                    'name_en' => "Q{$q} {$year}",
                    'start_date' => $dates['start_date'],
                    'end_date' => $dates['end_date'],
                    'is_created' => $existing !== null,
                    'tax_period_id' => $existing?->id,
                    'status' => $existing?->status,
                    'transactions_count' => $existing?->transactions_count ?? 0,
                    'income_count' => $existing?->income_transactions_count ?? 0,
                    'expense_count' => $existing?->expense_transactions_count ?? 0,
                    'sum_income_vat' => $existing ? (float) $existing->sum_income_vat_amount : 0.0,
                    'sum_expense_vat' => $existing ? (float) $existing->sum_expense_vat_amount : 0.0,
                    'net_vat_amount' => $existing ? (float) $existing->net_vat_amount : 0.0,
                    'posted_at' => $existing?->posted_at?->format('Y-m-d H:i'),
                ];
            }

            $result[] = [
                'year' => $year,
                'quarters' => $yearQuarters,
            ];
        }

        return $result;
    }

    /**
     * Parse period key (e.g., 2026-Q1) and return start/end dates and metadata.
     */
    public function parsePeriodKey(string $key): array
    {
        if (preg_match('/^(\d{4})-Q([1-4])$/', $key, $matches)) {
            $year = (int) $matches[1];
            $quarter = (int) $matches[2];
            $dates = $this->getQuarterDates($year, $quarter);

            return [
                'period_key' => $key,
                'year' => $year,
                'year_period' => (string) $quarter,
                'period_type' => 'quarterly',
                'name_ar' => "الربع {$this->getQuarterArabicName($quarter)} {$year}",
                'name_en' => "Q{$quarter} {$year}",
                'start_date' => $dates['start_date'],
                'end_date' => $dates['end_date'],
            ];
        }

        throw new InvalidArgumentException("Invalid period key format: {$key}. Expected YYYY-Q[1-4].");
    }

    /**
     * Get start and end dates for a quarter.
     */
    public function getQuarterDates(int $year, int $quarter): array
    {
        switch ($quarter) {
            case 1:
                return ['start_date' => "{$year}-01-01", 'end_date' => "{$year}-03-31"];
            case 2:
                return ['start_date' => "{$year}-04-01", 'end_date' => "{$year}-06-30"];
            case 3:
                return ['start_date' => "{$year}-07-01", 'end_date' => "{$year}-09-30"];
            case 4:
                return ['start_date' => "{$year}-10-01", 'end_date' => "{$year}-12-31"];
            default:
                throw new InvalidArgumentException('Quarter must be between 1 and 4.');
        }
    }

    public function getQuarterArabicName(int $quarter): string
    {
        return match ($quarter) {
            1 => 'الأول',
            2 => 'الثاني',
            3 => 'الثالث',
            4 => 'الرابع',
            default => (string) $quarter,
        };
    }

    /**
     * Collect all eligible transactions within the date range.
     */
    public function collectTransactionsForPeriod(
        int $tenantId,
        string $startDate,
        string $endDate,
        ?int $centerId = null,
        ?int $excludeTaxPeriodId = null
    ): array {
        // Find transaction IDs already posted in another TaxPeriod
        $postedQuery = DB::table('tax_period_transactions')
            ->join('tax_periods', 'tax_periods.id', '=', 'tax_period_transactions.tax_period_id')
            ->where('tax_periods.tenant_id', $tenantId)
            ->where('tax_periods.status', 'posted');

        if ($excludeTaxPeriodId) {
            $postedQuery->where('tax_periods.id', '!=', $excludeTaxPeriodId);
        }

        $postedRows = $postedQuery->select('tax_period_transactions.transaction_type', 'tax_period_transactions.transaction_id')->get();

        $alreadyPosted = [];

        foreach ($postedRows as $r) {
            $alreadyPosted[$r->transaction_type.'_'.$r->transaction_id] = true;
        }

        $transactions = [];

        // 1. Sales Invoices & Credit Notes
        $invoicesQuery = Invoice::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('status', '!=', 'cancelled')
            ->whereDate('issue_date', '>=', $startDate)
            ->whereDate('issue_date', '<=', $endDate);

        if ($centerId) {
            $invoicesQuery->where('center_id', $centerId);
        }

        $invoices = $invoicesQuery->get();

        foreach ($invoices as $inv) {
            $type = $inv->type === 'credit_note' ? 'credit_note' : 'sales_invoice';

            if (isset($alreadyPosted[$type.'_'.$inv->id])) {
                continue;
            }

            $isIncome = true;
            $sign = $inv->type === 'credit_note' ? -1 : 1;

            $subtotal = round(((float) $inv->total_excl_tax) * $sign, 2);
            $vatAmount = round(((float) $inv->total_tax) * $sign, 2);
            $totalAmount = round(((float) $inv->total_incl_tax) * $sign, 2);

            $transactions[] = [
                'transaction_type' => $type,
                'transaction_id' => $inv->id,
                'ref_no' => $inv->invoice_number ?? "INV-{$inv->id}",
                'transaction_date' => $inv->issue_date ? Carbon::parse($inv->issue_date)->format('Y-m-d') : null,
                'party_name' => $inv->customer_name_snapshot ?? ($inv->customer?->name ?? 'عميل نقدي'),
                'tax_number' => $inv->customer_vat_snapshot ?? null,
                'is_income' => $isIncome,
                'is_vat_inclusive' => true,
                'subtotal' => $subtotal,
                'vat_rate' => (float) ($inv->tax_rate_snapshot ?? 15.00),
                'vat_amount' => $vatAmount,
                'total_amount' => $totalAmount,
                'is_selected' => true,
                'notes' => $inv->notes ?? null,
            ];
        }

        // 2. Purchase Invoices
        $purchasesQuery = PurchaseInvoice::withoutGlobalScopes()
            ->with('supplier')
            ->where('tenant_id', $tenantId)
            ->where('status', '!=', 'cancelled')
            ->whereDate('issue_date', '>=', $startDate)
            ->whereDate('issue_date', '<=', $endDate);

        if ($centerId) {
            $purchasesQuery->where('center_id', $centerId);
        }

        $purchases = $purchasesQuery->get();

        foreach ($purchases as $p) {
            if (isset($alreadyPosted['purchase_invoice_'.$p->id])) {
                continue;
            }

            $subtotal = round((float) ($p->subtotal ?? 0), 2);
            $vatAmount = round((float) ($p->tax_amount ?? 0), 2);
            $totalAmount = round((float) ($p->total ?? ($subtotal + $vatAmount)), 2);

            $transactions[] = [
                'transaction_type' => 'purchase_invoice',
                'transaction_id' => $p->id,
                'ref_no' => $p->invoice_number ?? $p->code ?? "PI-{$p->id}",
                'transaction_date' => $p->issue_date ? Carbon::parse($p->issue_date)->format('Y-m-d') : null,
                'party_name' => $p->supplier?->name ?? 'مورد',
                'tax_number' => $p->supplier?->tax_number ?? null,
                'is_income' => false,
                'is_vat_inclusive' => false,
                'subtotal' => $subtotal,
                'vat_rate' => 15.00,
                'vat_amount' => $vatAmount,
                'total_amount' => $totalAmount,
                'is_selected' => true,
                'notes' => $p->notes ?? null,
            ];
        }

        // 3. Purchase Returns
        $returnsQuery = PurchaseReturnInvoice::withoutGlobalScopes()
            ->with('purchaseInvoice.supplier')
            ->where('tenant_id', $tenantId)
            ->whereDate('return_date', '>=', $startDate)
            ->whereDate('return_date', '<=', $endDate);

        if ($centerId) {
            $returnsQuery->where('center_id', $centerId);
        }

        $returns = $returnsQuery->get();

        foreach ($returns as $ret) {
            if (isset($alreadyPosted['purchase_return_'.$ret->id])) {
                continue;
            }

            // Purchase return reduces purchases & expense VAT
            $subtotal = -round((float) ($ret->subtotal ?? 0), 2);
            $vatAmount = -round((float) ($ret->tax_amount ?? 0), 2);
            $totalAmount = -round((float) ($ret->total ?? 0), 2);

            $transactions[] = [
                'transaction_type' => 'purchase_return',
                'transaction_id' => $ret->id,
                'ref_no' => $ret->code ?? "RET-{$ret->id}",
                'transaction_date' => $ret->return_date ? Carbon::parse($ret->return_date)->format('Y-m-d') : null,
                'party_name' => $ret->purchaseInvoice?->supplier?->name ?? 'مورد',
                'tax_number' => $ret->purchaseInvoice?->supplier?->tax_number ?? null,
                'is_income' => false,
                'is_vat_inclusive' => false,
                'subtotal' => $subtotal,
                'vat_rate' => 15.00,
                'vat_amount' => $vatAmount,
                'total_amount' => $totalAmount,
                'is_selected' => true,
                'notes' => $ret->notes ?? null,
            ];
        }

        // 4. Taxable Company Transactions (Expenses / Incomes)
        $txQuery = CompanyTransaction::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('is_taxable', true)
            ->where('tax_amount', '>', 0)
            ->whereDate('transaction_date', '>=', $startDate)
            ->whereDate('transaction_date', '<=', $endDate);

        if ($centerId) {
            $txQuery->where('center_id', $centerId);
        }

        $txList = $txQuery->get();

        foreach ($txList as $tx) {
            if (isset($alreadyPosted['company_transaction_'.$tx->id])) {
                continue;
            }

            $isIncome = $tx->transaction_type === 'income';
            $subtotal = round((float) ($tx->amount ?? 0), 2);
            $vatAmount = round((float) ($tx->tax_amount ?? 0), 2);
            $totalAmount = round((float) ($tx->total_amount ?? ($subtotal + $vatAmount)), 2);

            $transactions[] = [
                'transaction_type' => 'company_transaction',
                'transaction_id' => $tx->id,
                'ref_no' => "TX-{$tx->id}",
                'transaction_date' => $tx->transaction_date ? Carbon::parse($tx->transaction_date)->format('Y-m-d') : null,
                'party_name' => $tx->title ?? ($isIncome ? 'إيراد خاضع للضريبة' : 'مصروف خاضع للضريبة'),
                'tax_number' => null,
                'is_income' => $isIncome,
                'is_vat_inclusive' => false,
                'subtotal' => $subtotal,
                'vat_rate' => 15.00,
                'vat_amount' => $vatAmount,
                'total_amount' => $totalAmount,
                'is_selected' => true,
                'notes' => $tx->notes ?? null,
            ];
        }

        // Sort by transaction date descending
        usort($transactions, function ($a, $b) {
            return strcmp($b['transaction_date'] ?? '', $a['transaction_date'] ?? '');
        });

        return $transactions;
    }

    /**
     * Compute declaration summary from an array or collection of transactions.
     */
    public function computeSummary(array|Collection $transactions): array
    {
        $incomeCount = 0;
        $incomeSubtotal = 0.0;
        $incomeVat = 0.0;
        $incomeTotal = 0.0;

        $expenseCount = 0;
        $expenseSubtotal = 0.0;
        $expenseVat = 0.0;
        $expenseTotal = 0.0;

        foreach ($transactions as $tx) {
            $isSelected = is_array($tx) ? ($tx['is_selected'] ?? true) : ($tx->is_selected ?? true);

            if (! $isSelected) {
                continue;
            }

            $isIncome = is_array($tx) ? ($tx['is_income'] ?? true) : ($tx->is_income ?? true);
            $sub = (float) (is_array($tx) ? ($tx['subtotal'] ?? 0) : ($tx->subtotal ?? 0));
            $vat = (float) (is_array($tx) ? ($tx['vat_amount'] ?? 0) : ($tx->vat_amount ?? 0));
            $tot = (float) (is_array($tx) ? ($tx['total_amount'] ?? 0) : ($tx->total_amount ?? 0));

            if ($isIncome) {
                $incomeCount++;
                $incomeSubtotal += $sub;
                $incomeVat += $vat;
                $incomeTotal += $tot;
            } else {
                $expenseCount++;
                $expenseSubtotal += $sub;
                $expenseVat += $vat;
                $expenseTotal += $tot;
            }
        }

        return [
            'income' => [
                'count' => $incomeCount,
                'subtotal' => round($incomeSubtotal, 2),
                'vat_amount' => round($incomeVat, 2),
                'total_amount' => round($incomeTotal, 2),
            ],
            'expense' => [
                'count' => $expenseCount,
                'subtotal' => round($expenseSubtotal, 2),
                'vat_amount' => round($expenseVat, 2),
                'total_amount' => round($expenseTotal, 2),
            ],
            'total' => [
                'count' => $incomeCount + $expenseCount,
                'subtotal' => round($incomeSubtotal + $expenseSubtotal, 2),
                'vat_amount' => round($incomeVat + $expenseVat, 2),
                'total_amount' => round($incomeTotal + $expenseTotal, 2),
                'net_vat_amount' => round($incomeVat - $expenseVat, 2),
            ],
        ];
    }

    /**
     * Create a new TaxPeriod along with its transactions.
     */
    public function createPeriod(
        int $tenantId,
        string $periodKey,
        array $selectedTransactions,
        int $userId,
        ?int $centerId = null,
        ?string $notes = null
    ): TaxPeriod {
        $meta = $this->parsePeriodKey($periodKey);

        return DB::transaction(function () use ($tenantId, $centerId, $periodKey, $meta, $selectedTransactions, $userId, $notes) {
            // Delete any existing draft period for this key
            $existing = TaxPeriod::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('period_key', $periodKey)
                ->first();

            if ($existing) {
                if ($existing->isPosted()) {
                    throw new DomainException(__('reports.tax_periods_report.period_already_posted'));
                }
                $existing->transactions()->delete();
                $existing->delete();
            }

            $summary = $this->computeSummary($selectedTransactions);

            $period = TaxPeriod::create([
                'tenant_id' => $tenantId,
                'center_id' => $centerId,
                'period_key' => $periodKey,
                'name' => $meta['name_ar'],
                'year' => $meta['year'],
                'year_period' => $meta['year_period'],
                'period_type' => $meta['period_type'],
                'start_date' => $meta['start_date'],
                'end_date' => $meta['end_date'],
                'status' => 'draft',
                'transactions_count' => $summary['total']['count'],
                'income_transactions_count' => $summary['income']['count'],
                'expense_transactions_count' => $summary['expense']['count'],
                'sum_income_subtotal' => $summary['income']['subtotal'],
                'sum_income_vat_amount' => $summary['income']['vat_amount'],
                'sum_income_total_amount' => $summary['income']['total_amount'],
                'sum_expense_subtotal' => $summary['expense']['subtotal'],
                'sum_expense_vat_amount' => $summary['expense']['vat_amount'],
                'sum_expense_total_amount' => $summary['expense']['total_amount'],
                'sum_subtotal' => $summary['total']['subtotal'],
                'sum_vat_amount' => $summary['total']['vat_amount'],
                'sum_total_amount' => $summary['total']['total_amount'],
                'net_vat_amount' => $summary['total']['net_vat_amount'],
                'created_by' => $userId,
                'notes' => $notes,
            ]);

            foreach ($selectedTransactions as $tx) {
                $period->transactions()->create([
                    'tenant_id' => $tenantId,
                    'center_id' => $centerId,
                    'transaction_type' => $tx['transaction_type'],
                    'transaction_id' => $tx['transaction_id'] ?? null,
                    'ref_no' => $tx['ref_no'],
                    'transaction_date' => $tx['transaction_date'],
                    'party_name' => $tx['party_name'] ?? null,
                    'tax_number' => $tx['tax_number'] ?? null,
                    'is_income' => (bool) $tx['is_income'],
                    'is_vat_inclusive' => (bool) ($tx['is_vat_inclusive'] ?? false),
                    'subtotal' => (float) $tx['subtotal'],
                    'vat_rate' => (float) ($tx['vat_rate'] ?? 15.00),
                    'vat_amount' => (float) $tx['vat_amount'],
                    'total_amount' => (float) $tx['total_amount'],
                    'is_selected' => (bool) ($tx['is_selected'] ?? true),
                    'notes' => $tx['notes'] ?? null,
                ]);
            }

            $period->logActivity('created', "Tax period {$periodKey} created with {$period->transactions_count} transactions");

            return $period;
        });
    }

    /**
     * Post (lock and approve) a TaxPeriod.
     */
    public function postPeriod(TaxPeriod $period, int $userId): TaxPeriod
    {
        if ($period->isPosted()) {
            throw new DomainException(__('reports.tax_periods_report.period_already_posted'));
        }

        $period->recalculateTotals();
        $period->status = 'posted';
        $period->posted_by = $userId;
        $period->posted_at = now();
        $period->save();

        $period->logActivity('posted', "Tax period {$period->period_key} posted and locked");

        return $period;
    }

    /**
     * Recreate a draft TaxPeriod by rescanning all fresh transactions.
     */
    public function recreatePeriod(TaxPeriod $period, int $userId): TaxPeriod
    {
        if ($period->isPosted()) {
            throw new DomainException(__('reports.tax_periods_report.period_already_posted'));
        }

        $freshTransactions = $this->collectTransactionsForPeriod(
            $period->tenant_id,
            $period->start_date->format('Y-m-d'),
            $period->end_date->format('Y-m-d'),
            $period->center_id,
            $period->id
        );

        return DB::transaction(function () use ($period, $freshTransactions, $userId) {
            $period->transactions()->delete();

            foreach ($freshTransactions as $tx) {
                $period->transactions()->create([
                    'tenant_id' => $period->tenant_id,
                    'center_id' => $period->center_id,
                    'transaction_type' => $tx['transaction_type'],
                    'transaction_id' => $tx['transaction_id'] ?? null,
                    'ref_no' => $tx['ref_no'],
                    'transaction_date' => $tx['transaction_date'],
                    'party_name' => $tx['party_name'] ?? null,
                    'tax_number' => $tx['tax_number'] ?? null,
                    'is_income' => (bool) $tx['is_income'],
                    'is_vat_inclusive' => (bool) ($tx['is_vat_inclusive'] ?? false),
                    'subtotal' => (float) $tx['subtotal'],
                    'vat_rate' => (float) ($tx['vat_rate'] ?? 15.00),
                    'vat_amount' => (float) $tx['vat_amount'],
                    'total_amount' => (float) $tx['total_amount'],
                    'is_selected' => true,
                    'notes' => $tx['notes'] ?? null,
                ]);
            }

            $period->created_by = $userId;
            $period->recalculateTotals();
            $period->logActivity('recreated', "Tax period {$period->period_key} refreshed with fresh transactions");

            return $period;
        });
    }
}
