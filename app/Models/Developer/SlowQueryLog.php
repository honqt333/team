<?php

namespace App\Models\Developer;

use Illuminate\Database\Eloquent\Model;

class SlowQueryLog extends Model
{
    protected $table = 'dev_slow_queries_log';

    protected $fillable = [
        'sql_statement',
        'execution_time_ms',
        'caller',
        'caller_line',
        'type',
        'occurrences',
    ];
}
