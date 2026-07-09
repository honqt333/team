<?php

namespace App\Models\Developer;

use Illuminate\Database\Eloquent\Model;

// @bypass-tenancy-scanner - Developer tooling (audit/memory), outside tenant boundary
class AiMemory extends Model
{
    protected $table = 'dev_ai_memory';

    protected $fillable = [
        'module_name',
        'action_taken',
        'reason',
        'status',
        'refactored_at',
    ];

    protected $casts = [
        'refactored_at' => 'datetime',
    ];
}
