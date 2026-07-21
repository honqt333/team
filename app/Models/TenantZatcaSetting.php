<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantZatcaSetting extends Model
{
    use TenantScoped;

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
