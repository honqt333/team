<?php

namespace App\Models\Concerns;

use App\Support\TenancyContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait CenterScoped
{
    /**
     * Boot the center scoped trait for a model.
     */
    protected static function bootCenterScoped(): void
    {
        // Apply global scope only when context is available
        static::addGlobalScope('center_scoped', function (Builder $builder) {
            $tenantId = TenancyContext::tenantId();
            $centerId = TenancyContext::centerId();
            $model = $builder->getModel();
            $table = $model->getTable();

            // Only apply scope if both tenant and center context are available
            if ($tenantId !== null && $centerId !== null) {
                // Check if model has 'source' column (via fillable or explicit property)
                $hasSourceColumn = in_array('source', $model->getFillable()) 
                    || (property_exists($model, 'hasSourceColumn') && $model->hasSourceColumn);

                if ($hasSourceColumn) {
                    // Include center-scoped data OR system-level data (source = 'system')
                    $builder->where(function ($query) use ($table, $tenantId, $centerId) {
                        $query->where(function ($q) use ($table, $tenantId, $centerId) {
                            $q->where($table . '.tenant_id', $tenantId)
                              ->where($table . '.center_id', $centerId);
                        })->orWhere($table . '.source', 'system');
                    });
                } else {
                    // Standard center scope without source check
                    $builder->where($table . '.tenant_id', $tenantId)
                            ->where($table . '.center_id', $centerId);
                }
            }
        });

        // Auto-fill tenant_id and center_id on creating
        static::creating(function (Model $model) {
            $tenantId = TenancyContext::tenantId();
            $centerId = TenancyContext::centerId();

            // Only auto-fill if context is available and field is empty
            if ($tenantId !== null && empty($model->tenant_id)) {
                $model->tenant_id = $tenantId;
            }

            if ($centerId !== null && empty($model->center_id)) {
                $model->center_id = $centerId;
            }
        });
    }
}
