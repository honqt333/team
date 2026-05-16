<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseInvoiceLine extends Model
{
    protected $fillable = [
        'purchase_invoice_id', 'part_id',
        'qty', 'unit_cost', 'tax_rate', 'tax_amount', 'total',
    ];

    protected $casts = [
        'qty'       => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'tax_rate'  => 'decimal:2',
        'tax_amount'=> 'decimal:2',
        'total'     => 'decimal:2',
    ];

    public function purchaseInvoice() { return $this->belongsTo(PurchaseInvoice::class); }
    public function part()            { return $this->belongsTo(Part::class); }
}
