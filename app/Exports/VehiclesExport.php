<?php

namespace App\Exports;

use App\Models\Vehicle;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class VehiclesExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    public function collection()
    {
        $user = auth()->user();
        $search = request('search');

        $query = Vehicle::query()
            ->where('tenant_id', $user->tenant_id)
            ->where('center_id', $user->current_center_id)
            ->with(['customer', 'make', 'model']);

        if ($search) {
             $query->where(function ($q) use ($search) {
                $q->where('plate_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%"));
            });
        }

        return $query->latest()->get()->map(function ($vehicle) {
            return [
                'plate_number' => $vehicle->plate_number,
                'make' => $vehicle->make ? $vehicle->make->name : '-',
                'model' => $vehicle->model ? $vehicle->model->name : '-',
                'year' => $vehicle->year,
                'color' => $vehicle->color,
                'customer_name' => $vehicle->customer ? $vehicle->customer->name : '-',
                'customer_phone' => $vehicle->customer ? $vehicle->customer->phone : '-',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'رقم اللوحة / Plate Number',
            'الشركة المصنعة / Make',
            'الموديل / Model',
            'السنة / Year',
            'اللون / Color',
            'اسم العميل / Customer Name',
            'رقم الهاتف / Phone Number',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
