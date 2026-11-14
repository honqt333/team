<?php

declare(strict_types=1);

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
        // 1. Independent tenant_scoped global scope:
        // Enforces tenant_id whenever tenant context exists, even if center_id is null
        // or if withoutGlobalScope('center_scoped') is called.
        static::addGlobalScope('tenant_scoped', function (Builder $builder) {
            $tenantId = TenancyContext::tenantId();
            $model = $builder->getModel();
            $table = $model->getTable();

            if ($tenantId !== null) {
                $hasSourceColumn = in_array('source', $model->getFillable())
                    || (property_exists($model, 'hasSourceColumn') && $model->hasSourceColumn);

                if ($hasSourceColumn) {
                    $builder->where(function ($query) use ($table, $tenantId) {
                        $query->where($table.'.tenant_id', $tenantId)
                            ->orWhere($table.'.source', 'system');
                    });
                } else {
                    $builder->where($table.'.tenant_id', $tenantId);
                }
            }
        });

        // 2. Independent center_scoped global scope:
        // Enforces center_id when center context is active.
        static::addGlobalScope('center_scoped', function (Builder $builder) {
            $centerId = TenancyContext::centerId();
            $model = $builder->getModel();
            $table = $model->getTable();

            if ($centerId !== null) {
                $hasSourceColumn = in_array('source', $model->getFillable())
                    || (property_exists($model, 'hasSourceColumn') && $model->hasSourceColumn);

                if ($hasSourceColumn) {
                    $builder->where(function ($query) use ($table, $centerId) {
                        $query->where($table.'.center_id', $centerId)
                            ->orWhere($table.'.source', 'system');
                    });
                } else {
                    $builder->where($table.'.center_id', $centerId);
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
