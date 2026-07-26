<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Billing\SubscriptionInvoice;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Phase 5 — Aging report.
 *
 * Lists every unpaid invoice bucketed by days past due (0-30, 31-60,
 * 61-90, 90+). A finance staple — the admin uses it to prioritise
 * collection calls and write off bad debt.
 *
 * The bucket column is computed per row in map() so we can use a
 * single query without CASE branches on the SQL side (which would
 * differ between MySQL/Postgres/SQLite).
 */
class AgingReportExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    public function query()
    {
        return SubscriptionInvoice::query()
            ->with('tenant', 'subscription.plan')
            ->whereIn('status', ['pending', 'pending_payment', 'overdue'])
            ->orderBy('due_at');
    }

    public function headings(): array
    {
        return [
            'Invoice #',
            'Tenant',
            'Plan',
            'Cycle',
            'Total',
            'Due Date',
            'Days Overdue',
            'Bucket',
            'Status',
        ];
    }

    public function map($invoice): array
    {
        $daysOverdue = (int) $invoice->due_at?->diffInDays(now(), false);
        $bucket = match (true) {
            $daysOverdue <= 30 => '0-30 days',
            $daysOverdue <= 60 => '31-60 days',
            $daysOverdue <= 90 => '61-90 days',
            default => '90+ days',
        };

        return [
            $invoice->invoice_number,
            $invoice->tenant?->trade_name ?? $invoice->tenant?->name ?? '—',
            $invoice->subscription?->plan?->name ?? '—',
            $invoice->subscription?->billing_cycle ?? '—',
            number_format((float) $invoice->total, 2),
            $invoice->due_at?->toDateString() ?? '—',
            $daysOverdue,
            $bucket,
            $invoice->status,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
