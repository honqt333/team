<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxPeriodTransaction extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'tax_period_id',
        'tenant_id',
        'center_id',
        'transaction_type',
        'transaction_id',
        'ref_no',
        'transaction_date',
        'party_name',
        'tax_number',
        'is_income',
        'is_vat_inclusive',
        'subtotal',
        'vat_rate',
        'vat_amount',
        'total_amount',
        'is_selected',
        'notes',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'is_income' => 'boolean',
        'is_vat_inclusive' => 'boolean',
        'is_selected' => 'boolean',
        'subtotal' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function taxPeriod(): BelongsTo
    {
        return $this->belongsTo(TaxPeriod::class, 'tax_period_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }
}
