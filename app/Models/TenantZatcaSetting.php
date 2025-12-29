<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantZatcaSetting extends Model
{
    protected $fillable = [
        'tenant_id',
        'qr_enabled',
    ];

    protected $casts = [
        'qr_enabled' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
