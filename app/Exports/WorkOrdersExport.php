<?php

namespace App\Exports;

use App\Models\WorkOrder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WorkOrdersExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    public function collection()
    {
        $query = WorkOrder::query()
            ->with(['customer', 'vehicle'])
            ->orderBy('created_at', 'desc');

        // Search filter
        if (request('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('vehicle', function ($vq) use ($search) {
                      $vq->where('plate_number', 'like', "%{$search}%");
                  });
            });
        }

        // Status filter
        if (request('status')) {
            $status = request('status');
            if ($status === 'open') {
                $query->whereNotIn('status', ['paid', 'cancelled']);
            } elseif ($status === 'closed') {
                $query->whereIn('status', ['paid', 'cancelled']);
            }
        }

        // Customer type filter
        if (request('customer_type')) {
            $query->whereHas('customer', function ($cq) {
                $cq->where('type', request('customer_type'));
            });
        }

        // Date range filters
        if (request('date_from')) {
            $query->whereDate('created_at', '>=', request('date_from'));
        }
        if (request('date_to')) {
            $query->whereDate('created_at', '<=', request('date_to'));
        }

        return $query->get()->map(function ($order) {
            return [
                'code' => $order->code,
                'customer' => $order->customer?->name ?? '-',
                'vehicle' => $order->vehicle?->plate_number ?? '-',
                'status' => $order->status,
                'expected_end_date' => $order->expected_end_date?->format('Y-m-d') ?? '-',
                'total' => number_format($order->total, 2),
                'paid' => number_format($order->paid_amount, 2),
                'balance' => number_format($order->total - $order->paid_amount, 2),
                'created_at' => $order->created_at->format('Y-m-d'),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'رقم الكرت / Code',
            'العميل / Customer',
            'المركبة / Vehicle',
            'الحالة / Status',
            'تاريخ الانتهاء المتوقع / Expected End',
            'الإجمالي / Total',
            'المدفوع / Paid',
            'الباقي / Balance',
            'تاريخ الإنشاء / Created At',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
