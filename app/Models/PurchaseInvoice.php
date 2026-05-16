<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseInvoice extends Model
{
    use SoftDeletes;

    const STATUS_DRAFT     = 'draft';
    const STATUS_OPEN      = 'open';
    const STATUS_PAID      = 'paid';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'tenant_id', 'center_id', 'supplier_id', 'purchase_order_id',
        'invoice_number', 'code', 'issue_date', 'due_date', 'status',
        'subtotal', 'tax_amount', 'total', 'notes', 'attachment_path',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date'   => 'date',
        'subtotal'   => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total'      => 'decimal:2',
    ];

    // Relationships
    public function supplier()      { return $this->belongsTo(Supplier::class); }
    public function purchaseOrder() { return $this->belongsTo(PurchaseOrder::class); }
    public function lines()         { return $this->hasMany(PurchaseInvoiceLine::class); }
    public function tenant()        { return $this->belongsTo(Tenant::class); }
    public function center()        { return $this->belongsTo(Center::class); }

    // Status helpers
    public function isDraft()     { return $this->status === self::STATUS_DRAFT; }
    public function isOpen()      { return $this->status === self::STATUS_OPEN; }
    public function isPaid()      { return $this->status === self::STATUS_PAID; }
    public function isCancelled() { return $this->status === self::STATUS_CANCELLED; }

    // Code generator: PINV-0001
    public static function generateCode(int $tenantId): string
    {
        $last = static::where('tenant_id', $tenantId)->lockForUpdate()->count();
        return 'PINV-' . str_pad($last + 1, 4, '0', STR_PAD_LEFT);
    }
}
