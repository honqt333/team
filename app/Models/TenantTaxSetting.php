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
        'pricing_mode',
        'rounding_mode',
        'currency_code',
        'tax_number',
    ];

    protected $casts = [
        'vat_enabled' => 'boolean',
        'vat_rate' => 'decimal:2',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
