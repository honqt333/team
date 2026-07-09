<?php

namespace App\Models\Developer;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

// @bypass-tenancy-scanner - Developer tooling (audit snapshots), outside tenant boundary
class AuditSnapshot extends Model
{
    protected $table = 'dev_audit_snapshots';

    protected $fillable = [
        'score_overall',
        'score_architecture',
        'score_security',
        'score_performance',
        'score_testing',
        'score_ui',
        'score_documentation',
        'files_analyzed_count',
        'violations_count',
        'created_by',
    ];

    public function violations(): HasMany
    {
        return $this->hasMany(AuditViolation::class, 'snapshot_id');
    }

    public function componentStats(): HasMany
    {
        return $this->hasMany(ComponentStat::class, 'snapshot_id');
    }
}
