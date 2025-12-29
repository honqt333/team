<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'description',
        'qty',
        'unit_price',
        'is_taxable',
        'tax_category_code',
        'tax_rate_snapshot',
        'tax_amount',
        'line_total_excl_tax',
        'line_total_incl_tax',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'is_taxable' => 'boolean',
        'tax_rate_snapshot' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'line_total_excl_tax' => 'decimal:2',
        'line_total_incl_tax' => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
