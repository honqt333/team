<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantAddress extends Model
{
    protected $fillable = [
        'tenant_id',
        'address_line',
        'street',
        'city',
        'district',
        'building_number',
        'postal_code',
        'latitude',
        'longitude',
    ];

    
    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
