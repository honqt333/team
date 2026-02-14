<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantTaxSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'vat_enabled',
        'vat_rate',
        'services_vat_rate',
        'parts_vat_rate',
        'services_inclusive',
        'parts_inclusive',
        'show_amount_before_vat',
        'pricing_mode',
        'rounding_mode',
        'currency_code',
        'tax_number',
    ];

    protected $casts = [
        'vat_enabled' => 'boolean',
        'vat_rate' => 'decimal:2',
        'services_vat_rate' => 'decimal:2',
        'parts_vat_rate' => 'decimal:2',
        'services_inclusive' => 'boolean',
        'parts_inclusive' => 'boolean',
        'show_amount_before_vat' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
