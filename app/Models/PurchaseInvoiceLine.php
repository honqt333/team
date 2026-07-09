<?php

namespace App\Models;

use App\Models\Concerns\CenterScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseInvoiceLine extends Model
{
    use CenterScoped;

    protected $fillable = [
        'purchase_invoice_id', 'tenant_id', 'center_id', 'part_id',
        'qty', 'unit_cost', 'tax_rate', 'tax_amount', 'total',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function purchaseInvoice()
    {
        return $this->belongsTo(PurchaseInvoice::class);
    }

    public function part()
    {
        return $this->belongsTo(Part::class);
    }

    public function returnLines()
    {
        return $this->hasMany(PurchaseReturnInvoiceLine::class);
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class, 'id')->whereRaw('1 = 0');
    }
}
