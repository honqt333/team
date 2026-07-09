<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseInvoice extends Model
{
    use SoftDeletes, TenantScoped, \App\Traits\HasPurchaseInvoiceRelations;

    const STATUS_DRAFT     = 'draft';
    const STATUS_OPEN      = 'open';
    const STATUS_PAID      = 'paid';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'tenant_id', 'center_id', 'supplier_id', 'purchase_order_id',
        'invoice_number', 'code', 'issue_date', 'due_date', 'status',
        'subtotal', 'tax_amount', 'discount_amount', 'total', 'balance', 'notes', 'attachment_path',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date'   => 'date',
        'subtotal'   => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total'      => 'decimal:2',
        'balance'    => 'decimal:2',
    ];

    // Status helpers
    public function isDraft()     { return $this->status === self::STATUS_DRAFT; }
    public function isOpen()      { return $this->status === self::STATUS_OPEN; }
    public function isPaid()      { return $this->status === self::STATUS_PAID; }
    public function isCancelled() { return $this->status === self::STATUS_CANCELLED; }

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

        return 'PINV-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
