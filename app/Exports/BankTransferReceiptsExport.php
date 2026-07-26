<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\BankTransferReceipt;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Phase 5 — Receipts Excel export.
 *
 * Honors the same filters as the admin receipts index: status
 * (pending/approved/rejected/all) and a free-text search over the
 * tenant's trade name. The query is built with FromQuery so filters
 * push down to the database — we never materialise the full table
 * into memory just to apply a WHERE.
 */
class BankTransferReceiptsExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    public function query()
    {
        $query = BankTransferReceipt::query()
            ->with(['tenant', 'subscription.plan', 'payment', 'reviewer']);

        if (request('status') && request('status') !== 'all') {
            $query->where('status', request('status'));
        }

        if (request('search')) {
            $search = request('search');
            $query->whereHas('tenant', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('trade_name', 'like', "%{$search}%");
            });
        }

        return $query->orderByDesc('created_at');
    }

    public function headings(): array
    {
        return [
            'ID',
            'Tenant',
            'Reference',
            'Transfer Amount',
            'Transfer Date',
            'Bank',
            'Account Holder',
            'Status',
            'Expected Amount',
            'Amount Diff',
            'Reviewed By',
            'Reviewed At',
            'Created At',
        ];
    }

    public function map($receipt): array
    {
        return [
            $receipt->id,
            $receipt->tenant?->trade_name ?? $receipt->tenant?->name ?? '—',
            $receipt->transfer_reference,
            number_format((float) $receipt->transfer_amount, 2),
            $receipt->transfer_date?->toDateString(),
            $receipt->bank_name,
            $receipt->account_name,
            $receipt->status,
            $receipt->payment ? number_format((float) $receipt->payment->amount, 2) : '—',
            $receipt->amount_diff !== null ? number_format($receipt->amount_diff, 2) : '—',
            $receipt->reviewer?->name ?? '—',
            $receipt->reviewed_at?->toDateTimeString(),
            $receipt->created_at->toDateTimeString(),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
