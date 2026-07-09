<?php

namespace App\Models\Developer;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// @bypass-tenancy-scanner - Developer tooling, child of AuditSnapshot
class ComponentStat extends Model
{
    protected $table = 'dev_component_stats';

    protected $fillable = [
        'snapshot_id',
        'component_name',
        'count_compliant',
        'count_violations',
    ];

    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(AuditSnapshot::class, 'snapshot_id');
    }
}
