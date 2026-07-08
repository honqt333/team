<?php

namespace App\Models\Developer;

use Illuminate\Database\Eloquent\Model;

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
