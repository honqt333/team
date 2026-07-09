<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use LogicException;

class Prompt extends Model
{
    use HasFactory, SoftDeletes, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'key',
        'version',
        'content',
        'model',
        'temperature',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'temperature' => 'decimal:2',
            'active' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function scopeForTenant(Builder $query, int|Tenant $tenant): Builder
    {
        $tenantId = $tenant instanceof Tenant ? $tenant->getKey() : $tenant;

        return $query->where('tenant_id', $tenantId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    protected static function booted(): void
    {
        static::creating(function (Prompt $prompt): void {
            if (empty($prompt->tenant_id)) {
                throw new LogicException('Prompt requires a tenant_id.');
            }
        });
    }
}
