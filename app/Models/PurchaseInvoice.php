<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use App\Services\PaymentService;
use App\Traits\HasPurchaseInvoiceRelations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseInvoice extends Model
{
    use HasPurchaseInvoiceRelations, SoftDeletes, TenantScoped;

    const STATUS_DRAFT = 'draft';

    const STATUS_OPEN = 'open';

    const STATUS_PAID = 'paid';

    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'tenant_id', 'center_id', 'supplier_id', 'purchase_order_id', 'work_order_id',
        'invoice_number', 'code', 'issue_date', 'due_date', 'status',
        'subtotal', 'tax_amount', 'discount_amount', 'total', 'balance', 'notes', 'attachment_path',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    protected $appends = [
        'source_type',
    ];

    public function getSourceTypeAttribute(): string
    {
        if (! empty($this->attributes['source_type'])) {
            return (string) $this->attributes['source_type'];
        }

        if ($this->purchase_order_id !== null) {
            return 'warehouse';
        }

        if (array_key_exists('lines_count', $this->attributes) && (int) $this->attributes['lines_count'] > 0) {
            return 'warehouse';
        }

        if ($this->relationLoaded('lines') && $this->lines->isNotEmpty()) {
            return 'warehouse';
        }

        if (str_contains((string) ($this->notes ?? ''), 'GRN')) {
            return 'warehouse';
        }

        return 'external';
    }

    // Status helpers
    public function isDraft()
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isOpen()
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function isPaid()
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isCancelled()
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function updatePaymentStatus(): void
    {
        app(PaymentService::class)->recalculatePurchaseInvoice($this);
    }

    // Code generator: PINV-0001
    public static function generateCode(int $tenantId): string
    {
        $last = static::where('tenant_id', $tenantId)
            ->withTrashed()
            ->latest('id')
            ->first();

        $nextNumber = 1;

        if ($last && $last->code) {
            $lastNumber = (int) preg_replace('/[^0-9]/', '', $last->code);

            if ($lastNumber > 0) {
                $nextNumber = $lastNumber + 1;
            }
        }

        return 'PINV-'.str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
