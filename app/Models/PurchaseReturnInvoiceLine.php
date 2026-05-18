<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseReturnInvoiceLine extends Model
{
    protected $fillable = [
        'purchase_return_invoice_id', 'purchase_invoice_line_id', 'part_id',
        'qty', 'unit_cost', 'tax_rate', 'tax_amount', 'total'
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    // Relationships
    public function purchaseReturnInvoice() { return $this->belongsTo(PurchaseReturnInvoice::class); }
    public function purchaseInvoiceLine() { return $this->belongsTo(PurchaseInvoiceLine::class); }
    public function part() { return $this->belongsTo(Part::class); }
}
