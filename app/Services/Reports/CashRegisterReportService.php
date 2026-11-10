<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Models\CompanyTransaction;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CashRegisterReportService
{
    /**
     * Build raw cash register items from Payments and CompanyTransactions.
     *
     * @param array<int>|null $accessibleCenterIds
     * @return Collection<int, array<string, mixed>>
     */
    public function buildCashRegisterData(
        int $tenantId,
        ?int $centerId,
        ?Carbon $from,
        ?Carbon $to,
        string $paymentMethod = 'all',
        string $transactionType = 'all',
        string $userFilter = 'all',
        string $centerFilter = 'all',
        string $search = '',
        ?array $accessibleCenterIds = null
    ): Collection {
        $targetCenterIds = null;

        if ($centerFilter !== 'all' && is_numeric($centerFilter)) {
            $cId = (int) $centerFilter;

            if ($accessibleCenterIds === null || in_array($cId, $accessibleCenterIds, true)) {
                $targetCenterIds = [$cId];
            } else {
                $targetCenterIds = [];
            }
        } elseif ($accessibleCenterIds !== null) {
            $targetCenterIds = $accessibleCenterIds;
        }

        $items = collect();

        // 1. Payments Table Records
        if ($transactionType === 'all' || in_array($transactionType, ['sales_payment', 'purchase_payment', 'refund', 'bad_debt'], true)) {
            $payQuery = Payment::query()
                ->where('tenant_id', $tenantId)
                ->with(['invoice.customer', 'purchaseInvoice.supplier', 'workOrder.customer', 'receivedBy', 'center']);

            if ($targetCenterIds !== null) {
                $payQuery->where(function ($q) use ($targetCenterIds) {
                    $q->whereIn('center_id', $targetCenterIds)->orWhereNull('center_id');
                });
            }

            if ($from && $to) {
                $payQuery->whereBetween('payment_date', [$from, $to]);
            }

            if ($paymentMethod !== 'all') {
                $payQuery->where('payment_method', $paymentMethod);
            }

            if ($userFilter !== 'all' && is_numeric($userFilter)) {
                $payQuery->where('received_by', (int) $userFilter);
            }

            if ($transactionType === 'sales_payment') {
                $payQuery->whereNull('purchase_invoice_id')->where('type', 'payment');
            } elseif ($transactionType === 'purchase_payment') {
                $payQuery->whereNotNull('purchase_invoice_id');
            } elseif ($transactionType === 'refund') {
                $payQuery->where('type', 'refund');
            } elseif ($transactionType === 'bad_debt') {
                $payQuery->where('type', 'bad_debt');
            }

            if (! empty($search)) {
                $payQuery->where(function ($q) use ($search) {
                    $q->where('reference', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%")
                        ->orWhereHas('invoice', fn ($iq) => $iq->where('invoice_number', 'like', "%{$search}%")->orWhereHas('customer', fn ($cq) => $cq->where('name', 'like', "%{$search}%")))
                        ->orWhereHas('workOrder', fn ($wq) => $wq->where('code', 'like', "%{$search}%")->orWhereHas('customer', fn ($cq) => $cq->where('name', 'like', "%{$search}%")))
                        ->orWhereHas('purchaseInvoice', fn ($piq) => $piq->where('code', 'like', "%{$search}%")->orWhere('invoice_number', 'like', "%{$search}%")->orWhereHas('supplier', fn ($sq) => $sq->where('name', 'like', "%{$search}%")));
                });
            }

            $payments = $payQuery->get();

            foreach ($payments as $p) {
                $isPurchase = ! empty($p->purchase_invoice_id);
                $isRefund = ($p->type->value ?? $p->type) === 'refund';
                $isBadDebt = ($p->type->value ?? $p->type) === 'bad_debt';

                $cat = 'sales_payment';
                $debit = (float) $p->amount;
                $credit = 0.0;
                $refNo = $p->invoice->invoice_number ?? ($p->workOrder->code ?? ($p->reference ?: 'PAY-'.str_pad((string) $p->id, 5, '0', STR_PAD_LEFT)));
                $desc = __('reports.cash_register_report.sales_payment_desc').' '.($p->invoice?->customer->name ?? $p->workOrder?->customer->name ?? __('reports.cash_register_report.cash_customer'));

                if ($isPurchase) {
                    $cat = 'purchase_payment';
                    $debit = 0.0;
                    $credit = (float) $p->amount;
                    $refNo = $p->purchaseInvoice->code ?? ($p->purchaseInvoice->invoice_number ?? ($p->reference ?: 'PAY-'.$p->id));
                    $desc = __('reports.cash_register_report.purchase_payment_desc').' '.($p->purchaseInvoice?->supplier->name ?? __('reports.cash_register_report.general_supplier'));
                } elseif ($isRefund) {
                    $cat = 'refund';
                    $debit = 0.0;
                    $credit = (float) $p->amount;
                    $refNo = $p->reference ?: ($p->invoice?->invoice_number ?: 'REF-'.$p->id);
                    $desc = __('reports.cash_register_report.refund_desc').' '.($p->invoice?->customer->name ?? $p->workOrder?->customer->name ?? __('reports.cash_register_report.general_customer'));
                } elseif ($isBadDebt) {
                    $cat = 'bad_debt';
                    $debit = 0.0;
                    $credit = (float) $p->amount;
                    $refNo = $p->reference ?: 'BD-'.$p->id;
                    $desc = __('reports.cash_register_report.bad_debt_desc').' '.($p->invoice?->customer->name ?? __('reports.cash_register_report.general_customer'));
                }

                $items->push([
                    'id' => 'pay_'.$p->id,
                    'raw_id' => $p->id,
                    'type_group' => 'payment',
                    'ref_no' => $refNo,
                    'description' => $desc,
                    'category' => $cat,
                    'category_label' => ($catTr = __('reports.cash_register_report.categories.'.$cat)) !== 'reports.cash_register_report.categories.'.$cat ? $catTr : $cat,
                    'payment_mode' => $p->payment_method ?: 'cash',
                    'payment_mode_label' => ($mTr = __('reports.cash_register_report.methods.'.($p->payment_method ?: 'cash'))) !== 'reports.cash_register_report.methods.'.($p->payment_method ?: 'cash') ? $mTr : ($p->payment_method_label ?: ($p->payment_method ?: 'cash')),
                    'transaction_date' => $p->payment_date ? Carbon::parse($p->payment_date)->format('Y-m-d H:i') : Carbon::parse($p->created_at)->format('Y-m-d H:i'),
                    'timestamp' => $p->payment_date ? Carbon::parse($p->payment_date)->timestamp : Carbon::parse($p->created_at)->timestamp,
                    'debit' => $debit,
                    'credit' => $credit,
                    'user_id' => $p->received_by,
                    'user_name' => $p->receivedBy->name ?? __('reports.cash_register_report.system_admin'),
                    'user_photo' => $p->receivedBy->photo_url ?? null,
                    'center_id' => $p->center_id,
                    'center_name' => $p->center->name ?? __('reports.cash_register_report.main_center'),
                ]);
            }
        }

        // 2. Company Transactions (Revenue & Expenses)
        $includeCompanyTransactions = ($paymentMethod === 'all' || $paymentMethod === 'cash')
            && ($transactionType === 'all' || in_array($transactionType, ['revenue', 'expense'], true));

        if ($includeCompanyTransactions) {
            $trxQuery = CompanyTransaction::query()
                ->where('tenant_id', $tenantId)
                ->where('status', 'approved')
                ->whereNull('invoice_id')
                ->whereNull('purchase_invoice_id')
                ->with(['center']);

            if ($targetCenterIds !== null) {
                $trxQuery->where(function ($q) use ($targetCenterIds) {
                    $q->whereIn('center_id', $targetCenterIds)->orWhereNull('center_id');
                });
            }

            if ($from && $to) {
                $trxQuery->whereBetween('transaction_date', [$from->toDateString(), $to->toDateString()]);
            }

            if ($userFilter !== 'all' && is_numeric($userFilter)) {
                $trxQuery->where(function ($q) use ($userFilter) {
                    $q->where('approved_by', (int) $userFilter)->orWhere('updated_by', (int) $userFilter);
                });
            }

            if ($transactionType === 'revenue') {
                $trxQuery->where('transaction_type', 'revenue');
            } elseif ($transactionType === 'expense') {
                $trxQuery->where('transaction_type', 'expense');
            }

            if (! empty($search)) {
                $trxQuery->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%");
                });
            }

            $transactions = $trxQuery->get();

            foreach ($transactions as $trx) {
                $isRev = $trx->transaction_type === 'revenue';
                $cat = $isRev ? 'revenue' : 'expense';
                $debit = $isRev ? (float) $trx->total_amount : 0.0;
                $credit = $isRev ? 0.0 : (float) $trx->total_amount;
                $refNo = 'TRX-'.($isRev ? 'REV-' : 'EXP-').str_pad((string) $trx->id, 5, '0', STR_PAD_LEFT);
                $desc = ($isRev ? __('reports.cash_register_report.revenue_prefix').' ' : __('reports.cash_register_report.expense_prefix').' ').($trx->title ?: ($isRev ? __('reports.cash_register_report.operating_revenue') : __('reports.cash_register_report.general_expense')));

                $items->push([
                    'id' => 'trx_'.$trx->id,
                    'raw_id' => $trx->id,
                    'type_group' => 'company_transaction',
                    'ref_no' => $refNo,
                    'description' => $desc,
                    'category' => $cat,
                    'category_label' => __('reports.cash_register_report.categories.'.$cat),
                    'payment_mode' => 'cash',
                    'payment_mode_label' => __('reports.cash_register_report.methods.cash'),
                    'transaction_date' => Carbon::parse($trx->transaction_date)->format('Y-m-d').' 12:00',
                    'timestamp' => Carbon::parse($trx->transaction_date)->timestamp,
                    'debit' => $debit,
                    'credit' => $credit,
                    'user_id' => $trx->approved_by ?? $trx->updated_by,
                    'user_name' => __('reports.cash_register_report.financial_officer'),
                    'user_photo' => null,
                    'center_id' => $trx->center_id,
                    'center_name' => $trx->center->name ?? __('reports.cash_register_report.main_center'),
                ]);
            }
        }

        return $items;
    }
}
