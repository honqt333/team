<?php

namespace App\Models;

use App\Models\Concerns\CenterScoped;
use App\Models\Concerns\HasTaxSnapshot;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes, CenterScoped, HasTaxSnapshot;

    protected $fillable = [
        'tenant_id',
        'center_id',
        'customer_id',
        'work_order_id',
        'invoice_number',
        'issue_date',
        'supply_date',
        'type', // invoice, credit_note, debit_note
        'subtype', // simplified, standard
        'status', // draft, valid, reported, cancelled
        
        // Snapshots
        'customer_name_snapshot',
        'customer_vat_snapshot',
        'customer_address_snapshot',
        
        'tax_enabled_snapshot',
        'pricing_mode_snapshot',
        'tax_rate_snapshot',
        'currency_code',
        
        // Totals
        'total_excl_tax',
        'total_tax',
        'total_incl_tax',
        'total_taxable_amount',
        'tax_breakdown',
        
        // ZATCA
        'zatca_qr_tlv',
        'zatca_uuid',
        'zatca_hash',
        'zatca_prev_hash',
        'xml_path',
    ];

    protected $casts = [
        'issue_date' => 'datetime',
        'supply_date' => 'date',
        'tax_enabled_snapshot' => 'boolean',
        'tax_rate_snapshot' => 'decimal:2',
        'total_excl_tax' => 'decimal:2',
        'total_tax' => 'decimal:2',
        'total_incl_tax' => 'decimal:2',
        'total_taxable_amount' => 'decimal:2',
        'tax_breakdown' => 'array',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }
    
    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
