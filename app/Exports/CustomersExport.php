<?php

namespace App\Exports;

use App\Models\Customer;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CustomersExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    public function collection()
    {
        $query = Customer::query();

        if (request('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (request('type')) {
            $query->where('type', request('type'));
        }

        $typeNames = [
            'individual' => 'فرد',
            'company' => 'شركة',
            'government' => 'جهة حكومية',
            'vip' => 'VIP',
        ];

        return $query->withCount('vehicles')->orderBy('name')->get()->map(function ($customer) use ($typeNames) {
            return [
                'name' => $customer->name,
                'type' => $typeNames[$customer->type] ?? $customer->type,
                'phone' => $customer->phone,
                'email' => $customer->email,
                'balance' => $customer->balance ?? 0,
                'vehicles_count' => $customer->vehicles_count ?? 0,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'الاسم / Name',
            'النوع / Type',
            'رقم الهاتف / Phone',
            'البريد الإلكتروني / Email',
            'الرصيد / Balance',
            'عدد المركبات / Vehicles Count',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
