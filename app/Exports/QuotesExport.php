<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Quote;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class QuotesExport implements FromCollection, ShouldAutoSize, WithHeadings, WithStyles
{
    public function collection()
    {
        $query = Quote::query()
            ->with(['customer', 'vehicle.make', 'vehicle.model', 'convertedWorkOrder:id,code'])
            ->orderBy('created_at', 'desc');

        // Search filter
        if (request('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    })
                    ->orWhereHas('vehicle', function ($vq) use ($search) {
                        $vq->where('plate_number', 'like', "%{$search}%");
                    });
            });
        }

        // Status filter
        if (request('status') && request('status') !== 'all') {
            $status = request('status');

            if ($status === 'pending') {
                $query->whereIn('status', [Quote::STATUS_DRAFT, Quote::STATUS_SENT]);
            } else {
                $query->where('status', $status);
            }
        }

        // Date range filters
        if (request('date_from')) {
            $query->whereDate('created_at', '>=', request('date_from'));
        }

        if (request('date_to')) {
            $query->whereDate('created_at', '<=', request('date_to'));
        }

        $statusLabels = [
            'draft' => 'مسودة / Draft',
            'sent' => 'مرسل / Sent',
            'approved' => 'معتمد / Approved',
            'rejected' => 'مرفوض / Rejected',
            'converted' => 'محول لأمر عمل / Converted',
        ];

        return $query->get()->map(function ($quote) use ($statusLabels) {
            $vehicleDesc = '-';

            if ($quote->vehicle) {
                $make = $quote->vehicle->make?->name ?? '';
                $model = $quote->vehicle->model?->name ?? '';
                $vehicleDesc = trim("{$make} {$model}");
            }

            return [
                'code' => $quote->code,
                'customer' => $quote->customer?->name ?? '-',
                'phone' => $quote->customer?->phone ?? '-',
                'plate_number' => $quote->vehicle?->plate_number ?? '-',
                'vehicle' => $vehicleDesc ?: '-',
                'status' => $statusLabels[$quote->status] ?? $quote->status,
                'issue_date' => $quote->created_at?->format('Y-m-d') ?? '-',
                'valid_until' => $quote->valid_until ? $quote->valid_until->format('Y-m-d') : '-',
                'subtotal' => number_format((float) $quote->subtotal, 2),
                'discount' => number_format((float) $quote->total_discount, 2),
                'tax' => number_format((float) $quote->total_tax, 2),
                'total' => number_format((float) $quote->total, 2),
                'converted_work_order' => $quote->convertedWorkOrder?->code ?? '-',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'رقم العرض / Quote Code',
            'العميل / Customer',
            'جوال العميل / Phone',
            'رقم اللوحة / Plate Number',
            'المركبة / Vehicle',
            'الحالة / Status',
            'تاريخ الإصدار / Issue Date',
            'صالح حتى / Valid Until',
            'المجموع الفرعي / Subtotal',
            'الخصم / Discount',
            'الضريبة / Tax',
            'الإجمالي / Grand Total (SAR)',
            'أمر العمل المرتبط / Work Order',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'D97706'], // Amber-600
                ],
            ],
        ];
    }
}
