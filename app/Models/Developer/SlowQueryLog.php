<?php

declare(strict_types=1);

namespace App\Models\Developer;

use Illuminate\Database\Eloquent\Model;

// @bypass-tenancy-scanner - Developer tooling (slow-query log)
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
