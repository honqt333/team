<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'type',
        'labels',
        'header_text',
        'footer_text',
        'terms_conditions',
        'show_logo',
        'show_qr',
        'is_default',
    ];

    protected $casts = [
        'labels' => 'array',
        'show_logo' => 'boolean',
        'show_qr' => 'boolean',
        'is_default' => 'boolean',
    ];

    /**
     * Default labels for invoice templates
     */
    public static function defaultLabels(): array
    {
        return [
            'document_title' => 'فاتورة ضريبية',
            'proforma_title' => 'فاتورة أولية',
            'invoice_number' => 'رقم الفاتورة',
            'date' => 'التاريخ',
            'supply_date' => 'تاريخ التوريد',
            'customer' => 'العميل',
            'vat_number' => 'الرقم الضريبي',
            'vehicle' => 'المركبة',
            'plate' => 'رقم اللوحة',
            'description' => 'الوصف',
            'qty' => 'الكمية',
            'unit_price' => 'سعر الوحدة',
            'total' => 'الإجمالي',
            'subtotal' => 'المجموع قبل الضريبة',
            'tax' => 'ضريبة القيمة المضافة',
            'tax_rate' => 'نسبة الضريبة',
            'grand_total' => 'الإجمالي شامل الضريبة',
            'payment_method' => 'طريقة الدفع',
            'paid' => 'المدفوع',
            'balance' => 'المتبقي',
            'payment_status' => 'حالة الدفع',
            'thank_you' => 'شكراً لزيارتكم',
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // Relationships
    // ─────────────────────────────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────

    /**
     * Get label with fallback to default
     */
    public function getLabel(string $key): string
    {
        $labels = $this->labels ?? [];
        $defaults = self::defaultLabels();

        return $labels[$key] ?? $defaults[$key] ?? $key;
    }

    /**
     * Get all labels merged with defaults
     */
    public function getAllLabels(): array
    {
        return array_merge(self::defaultLabels(), $this->labels ?? []);
    }

    /**
     * Get or create default template for tenant
     */
    public static function getDefault(int $tenantId, string $type = 'tax_invoice'): self
    {
        $template = static::where('tenant_id', $tenantId)
            ->where('type', $type)
            ->where('is_default', true)
            ->first();

        if (!$template) {
            $template = static::create([
                'tenant_id' => $tenantId,
                'name' => $type === 'proforma' ? 'فاتورة أولية' : 'فاتورة ضريبية',
                'type' => $type,
                'labels' => self::defaultLabels(),
                'is_default' => true,
            ]);
        }

        return $template;
    }
}
