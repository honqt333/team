<?php

declare(strict_types=1);

namespace App\Models\Billing;

use App\Models\Concerns\TenantScoped;
use App\Models\PaymentSettings;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionInvoice extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'subscription_id',
        'tenant_id',
        'invoice_number',
        'seller_name_snapshot',
        'seller_name_en_snapshot',
        'seller_vat_snapshot',
        'seller_cr_snapshot',
        'subtotal',
        'vat',
        'discount',
        'total',
        'status',
        'issued_at',
        'due_at',
        'paid_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'vat' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'issued_at' => 'datetime',
            'due_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * Freeze the platform issuer identity when the subscription invoice
     * is created. Regenerating the PDF later must not rewrite history
     * if the platform company settings change.
     */
    protected static function booted(): void
    {
        static::creating(function (SubscriptionInvoice $invoice): void {
            $settings = PaymentSettings::first();

            $invoice->seller_name_snapshot ??= (string) (
                $settings?->company_legal_name
                ?: ($settings?->company_legal_name_en ?: config('app.name_ar', 'خدمة برو'))
            );
            $invoice->seller_name_en_snapshot ??= (string) (
                $settings?->company_legal_name_en
                ?: ($settings?->company_legal_name ?: config('app.name', 'Khidmh Pro'))
            );
            $invoice->seller_vat_snapshot ??= (string) $settings?->company_vat_number;
            $invoice->seller_cr_snapshot ??= (string) $settings?->company_cr_number;
        });
    }

    /** @return array{name: string, name_en: string, vat_number: ?string, cr_number: ?string} */
    public function issuerIdentity(?PaymentSettings $settings): array
    {
        $hasIssuer = $this->seller_name_snapshot !== null || $this->seller_name_en_snapshot !== null;
        $name = $this->seller_name_snapshot
            ?? ($settings?->company_legal_name
                ?: ($settings?->company_legal_name_en ?: config('app.name_ar', 'خدمة برو')));
        $nameEn = $this->seller_name_en_snapshot
            ?? ($settings?->company_legal_name_en
                ?: ($settings?->company_legal_name ?: config('app.name', 'Khidmh Pro')));
        $vatNumber = $this->seller_vat_snapshot ?? ($hasIssuer ? null : $settings?->company_vat_number);
        $crNumber = $this->seller_cr_snapshot ?? ($hasIssuer ? null : $settings?->company_cr_number);

        return [
            'name' => $name,
            'name_en' => $nameEn,
            'vat_number' => $vatNumber,
            'cr_number' => $crNumber,
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class, 'subscription_invoice_id');
    }

    /**
     * Check if paid
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Check if overdue
     */
    public function isOverdue(): bool
    {
        return $this->status === 'pending' && $this->due_at?->isPast();
    }

    /**
     * Generate invoice number
     */
    public static function generateNumber(): string
    {
        $year = date('Y');
        $prefix = "INV-{$year}-";

        $latestNumber = self::withoutGlobalScopes()
            ->where('invoice_number', 'like', "{$prefix}%")
            ->orderByDesc('invoice_number')
            ->value('invoice_number');

        $nextSeq = $latestNumber
            ? ((int) substr((string) $latestNumber, strlen($prefix))) + 1
            : 1;

        $candidate = $prefix.str_pad((string) $nextSeq, 6, '0', STR_PAD_LEFT);

        while (self::withoutGlobalScopes()->where('invoice_number', $candidate)->exists()) {
            $nextSeq++;
            $candidate = $prefix.str_pad((string) $nextSeq, 6, '0', STR_PAD_LEFT);
        }

        return $candidate;
    }
}
