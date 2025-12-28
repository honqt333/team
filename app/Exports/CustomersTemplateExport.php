<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CustomersTemplateExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize
{
    public function array(): array
    {
        // Sample row for reference
        return [
            [
                'individual',
                'اسم العميل النموذجي',
                'اسم المسؤول (اختياري)',
                'email@example.com',
                '+966501234567',
                '+966501234567',
                '123456789',
                'ملاحظات اختيارية',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'النوع / Type',
            'الاسم / Name',
            'اسم المسؤول / Contact Name',
            'البريد الإلكتروني / Email',
            'رقم الهاتف / Phone (مطلوب)',
            'الواتساب / WhatsApp',
            'الرقم الضريبي / Tax Number',
            'ملاحظات / Notes',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Style header row
        $sheet->getStyle('A1:H1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'color' => ['rgb' => '4F46E5'],
            ],
        ]);

        // Style sample row
        $sheet->getStyle('A2:H2')->applyFromArray([
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'color' => ['rgb' => 'F3F4F6'],
            ],
            'font' => ['italic' => true, 'color' => ['rgb' => '6B7280']],
        ]);

        // Add instructions comment
        $sheet->getComment('A1')->getText()->createTextRun('القيم المسموحة: individual, company, government, vip');
        $sheet->getComment('E1')->getText()->createTextRun('رقم الهاتف مطلوب ويجب أن يكون فريداً');

        return [];
    }
}
