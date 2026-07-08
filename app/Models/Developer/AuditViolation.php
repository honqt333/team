<?php

namespace App\Models\Developer;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditViolation extends Model
{
    protected $table = 'dev_audit_violations';

    protected $fillable = [
        'snapshot_id',
        'category',
        'severity',
        'file_path',
        'line_number',
        'violation_code',
        'description',
    ];

    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(AuditSnapshot::class, 'snapshot_id');
    }
}
