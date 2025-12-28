<?php

namespace App\Imports;

use App\Models\Customer;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\Importable;

class CustomersImport implements ToModel, WithStartRow
{
    use Importable;

    protected int $imported = 0;
    protected array $importErrors = [];

    /**
     * Start from row 2 (skip header row)
     */
    public function startRow(): int
    {
        return 2;
    }

    public function model(array $row)
    {
        // Column mapping by index:
        // 0: Type, 1: Name, 2: Contact Name, 3: Email, 4: Phone, 5: WhatsApp, 6: Tax Number, 7: Notes

        $type = trim($row[0] ?? 'individual');
        $name = trim($row[1] ?? '');
        $contactName = trim($row[2] ?? '') ?: null;
        $email = trim($row[3] ?? '') ?: null;
        $phone = trim($row[4] ?? '');
        $whatsapp = trim($row[5] ?? '') ?: null;
        $taxNumber = trim($row[6] ?? '') ?: null;
        $notes = trim($row[7] ?? '') ?: null;

        // Skip if required fields are missing
        if (empty($name) || empty($phone)) {
            return null;
        }

        // Validate type
        if (!in_array($type, ['individual', 'company', 'government', 'vip'])) {
            $type = 'individual';
        }

        // Check for duplicate phone
        if (Customer::where('phone', $phone)->exists()) {
            $this->importErrors[] = "رقم الهاتف {$phone} موجود مسبقاً";
            return null;
        }

        try {
            $this->imported++;

            return new Customer([
                'type' => $type,
                'name' => $name,
                'contact_name' => $contactName,
                'email' => $email,
                'phone' => $phone,
                'whatsapp' => $whatsapp,
                'tax_number' => $taxNumber,
                'notes' => $notes,
            ]);
        } catch (\Exception $e) {
            $this->importErrors[] = "خطأ في استيراد: {$name}";
            return null;
        }
    }

    public function getImportedCount(): int
    {
        return $this->imported;
    }

    public function getImportErrors(): array
    {
        return $this->importErrors;
    }
}
