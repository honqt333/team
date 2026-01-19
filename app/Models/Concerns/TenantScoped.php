<?php

namespace App\Models\Concerns;

use App\Support\TenancyContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait TenantScoped
{
    /**
     * Boot the tenant scoped trait for a model.
     */
    protected static function bootTenantScoped(): void
    {
        // Apply global scope only when context is available
        static::addGlobalScope('tenant_scoped', function (Builder $builder) {
            $tenantId = TenancyContext::tenantId();
            $model = $builder->getModel();
            $table = $model->getTable();

            // Only apply scope if tenant context is available
            if ($tenantId !== null) {
                $builder->where($table . '.tenant_id', $tenantId);
            }
        });

        // Auto-fill tenant_id on creating
        static::creating(function (Model $model) {
            $tenantId = TenancyContext::tenantId();

            // Only auto-fill if context is available and field is empty
            if ($tenantId !== null && empty($model->tenant_id)) {
                $model->tenant_id = $tenantId;
            }
        });
    }
}
