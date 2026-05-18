<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseReturnInvoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id', 'center_id', 'purchase_invoice_id', 'code', 'return_date',
        'subtotal', 'tax_amount', 'total', 'notes', 'attachment_path'
    ];

    protected $casts = [
        'return_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    // Relationships
    public function purchaseInvoice() { return $this->belongsTo(PurchaseInvoice::class); }
    public function lines() { return $this->hasMany(PurchaseReturnInvoiceLine::class); }
    public function tenant() { return $this->belongsTo(Tenant::class); }
    public function center() { return $this->belongsTo(Center::class); }

    // Code generator: PRET-0001
    public static function generateCode(int $tenantId): string
    {
        $last = static::where('tenant_id', $tenantId)->withTrashed()->count();
        return 'PRET-' . str_pad($last + 1, 4, '0', STR_PAD_LEFT);
    }
}
