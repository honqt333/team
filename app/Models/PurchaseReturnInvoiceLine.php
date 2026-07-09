<?php

namespace App\Models;

use App\Models\Concerns\CenterScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseReturnInvoiceLine extends Model
{
    use CenterScoped;

    protected $fillable = [
        'purchase_return_invoice_id', 'tenant_id', 'center_id', 'purchase_invoice_line_id', 'part_id',
        'qty', 'unit_cost', 'tax_rate', 'tax_amount', 'total',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    // Relationships
    public function purchaseReturnInvoice()
    {
        return $this->belongsTo(PurchaseReturnInvoice::class);
    }

    public function purchaseInvoiceLine()
    {
        return $this->belongsTo(PurchaseInvoiceLine::class);
    }

    public function part()
    {
        return $this->belongsTo(Part::class);
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class, 'id')->whereRaw('1 = 0');
    }
}
