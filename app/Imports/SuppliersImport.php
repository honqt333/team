<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\Supplier;
use Exception;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;

class SuppliersImport implements ToModel, WithStartRow
{
    use Importable;

    protected int $imported = 0;

    protected array $importErrors = [];

    /**
     * Start from row 2 (skip header row).
     */
    public function startRow(): int
    {
        return 2;
    }

    public function model(array $row)
    {
        // 0: Type, 1: Name, 2: Contact Person, 3: Phone, 4: WhatsApp, 5: Email,
        // 6: Tax Number, 7: CR Number, 8: City, 9: Address, 10: Notes

        $rawType = trim((string) ($row[0] ?? 'parts'));
        $name = trim((string) ($row[1] ?? ''));
        $contactPerson = trim((string) ($row[2] ?? ''));
        $phone = trim((string) ($row[3] ?? '')) ?: null;
        $whatsapp = trim((string) ($row[4] ?? '')) ?: null;
        $email = trim((string) ($row[5] ?? '')) ?: null;
        $taxNumber = trim((string) ($row[6] ?? '')) ?: null;
        $crNumber = trim((string) ($row[7] ?? '')) ?: null;
        $city = trim((string) ($row[8] ?? '')) ?: null;
        $address = trim((string) ($row[9] ?? '')) ?: null;
        $notes = trim((string) ($row[10] ?? '')) ?: null;

        // Skip completely empty rows
        if (empty($name)) {
            return null;
        }

        // Normalize type
        $type = 'parts';
        $lowerType = mb_strtolower($rawType);

        if (str_contains($lowerType, 'service') || str_contains($lowerType, 'خدم')) {
            $type = 'services';
        }

        // If contact person is empty, use name
        if (empty($contactPerson)) {
            $contactPerson = $name;
        }

        $tenantId = auth()->user()?->tenant_id;
        $centerId = auth()->user()?->current_center_id;

        // Check for duplicate supplier name for this tenant
        if ($tenantId && Supplier::where('tenant_id', $tenantId)->where('name', $name)->exists()) {
            $this->importErrors[] = "المورد '{$name}' موجود مسبقاً في النظام";

            return null;
        }

        // Check for duplicate phone for this tenant if phone is given
        if ($phone && $tenantId && Supplier::where('tenant_id', $tenantId)->where('phone', $phone)->exists()) {
            $this->importErrors[] = "رقم الهاتف '{$phone}' للمورد '{$name}' مسجل مسبقاً لمورد آخر";

            return null;
        }

        try {
            $this->imported++;

            return new Supplier([
                'tenant_id' => $tenantId,
                'center_id' => $centerId,
                'name' => $name,
                'type' => $type,
                'contact_person' => $contactPerson,
                'phone' => $phone,
                'whatsapp' => $whatsapp ?: $phone,
                'email' => $email,
                'tax_number' => $taxNumber,
                'cr_number' => $crNumber,
                'city' => $city,
                'address' => $address,
                'notes' => $notes,
                'is_active' => true,
            ]);
        } catch (Exception $e) {
            $this->importErrors[] = "خطأ في استيراد '{$name}': ".$e->getMessage();

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
