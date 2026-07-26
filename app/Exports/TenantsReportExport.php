<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Tenant;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Phase 5 — Tenant roster export.
 *
 * One row per tenant with their current plan, status, signup date,
 * and last paid invoice. Useful for finance to reconcile MRR
 * outside the dashboard, and for sales/renewals to know who to
 * follow up with.
 */
class TenantsReportExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    public function query()
    {
        $query = Tenant::query()
            ->with([
                'subscriptions.plan',
                'subscriptions.invoices' => fn ($q) => $q->latest('issued_at')->limit(1),
            ]);

        if (request('status')) {
            $query->where('status', request('status'));
        }

        if (request('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('trade_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Trade Name',
            'Email',
            'Status',
            'Active Subscription',
            'Cycle',
            'Created At',
            'Last Invoice Date',
            'Last Invoice Amount',
        ];
    }

    public function map($tenant): array
    {
        $activeSub = $tenant->subscriptions->firstWhere('status', 'active');
        $lastInvoice = $activeSub?->invoices?->first();

        return [
            $tenant->id,
            $tenant->name,
            $tenant->trade_name,
            $tenant->email,
            $tenant->status,
            $activeSub?->plan?->name ?? '—',
            $activeSub?->billing_cycle ?? '—',
            $tenant->created_at->toDateString(),
            $lastInvoice?->issued_at?->toDateString() ?? '—',
            $lastInvoice ? number_format((float) $lastInvoice->total, 2) : '—',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
