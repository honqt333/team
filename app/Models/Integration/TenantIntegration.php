<?php

namespace App\Models\Integration;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantIntegration extends Model
{
    protected $fillable = [
        'tenant_id',
        'integration_id',
        'config',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'config' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class);
    }

    /**
     * Get merged config (system + tenant overrides).
     */
    public function getMergedConfig(): array
    {
        return array_merge(
            $this->integration->config ?? [],
            $this->config ?? []
        );
    }
}
