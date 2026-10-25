<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SuppliersTemplateExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles
{
    public function array(): array
    {
        // Sample rows for reference
        return [
            [
                'parts',
                'شركة قطع الغيار المتقدمة',
                'محمد أحمد',
                '+966501112233',
                '+966501112233',
                'supplier@example.com',
                '300123456789003',
                '1010123456',
                'الرياض',
                'شارع الصناعية القديمة',
                'مورد معتمد للقطع الأصلية',
            ],
            [
                'services',
                'مخرطة الوفاق لصيانة وتوضيب المحركات',
                'عبدالله السعيد',
                '+966559988776',
                '+966559988776',
                'services@example.com',
                '',
                '',
                'جدة',
                'حي المروة',
                'خدمات خراطة وتوضيب',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'النوع / Type (parts/services)',
            'اسم المورد / Supplier Name (مطلوب)',
            'الشخص المسؤول / Contact Person (مطلوب)',
            'رقم الهاتف / Phone',
            'الواتساب / WhatsApp',
            'البريد الإلكتروني / Email',
            'الرقم الضريبي / Tax Number',
            'السجل التجاري / CR Number',
            'المدينة / City',
            'العنوان / Address',
            'ملاحظات / Notes',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Style header row
        $sheet->getStyle('A1:K1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => ['rgb' => 'EA580C'], // Orange-600 for purchasing
            ],
        ]);

        // Style sample rows
        $sheet->getStyle('A2:K3')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => ['rgb' => 'FFF7ED'], // Orange-50
            ],
            'font' => ['italic' => true, 'color' => ['rgb' => '9A3412']],
        ]);

        // Add instructions comments
        $sheet->getComment('A1')->getText()->createTextRun('القيم المسموحة: parts (قطع غيار) أو services (خدمات)');
        $sheet->getComment('B1')->getText()->createTextRun('اسم المورد حقل إلزامي');
        $sheet->getComment('C1')->getText()->createTextRun('اسم الشخص المسؤول إلزامي (إذا ترك فارغاً سيتم استخدام اسم المورد)');
        $sheet->getComment('G1')->getText()->createTextRun('الرقم الضريبي مكون من 15 رقماً ويبدأ وينتهي بالرقم 3');

        return [];
    }
}
