<?php

declare(strict_types=1);

namespace App\Models\HR;

use App\Models\Center;
use App\Models\Concerns\CenterScoped;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

class AttendanceCorrectionRequest extends Model
{
    use CenterScoped, SoftDeletes;

    protected $table = 'hr_attendance_correction_requests';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'tenant_id',
        'center_id',
        'employee_id',
        'attendance_id',
        'date',
        'requested_check_in',
        'requested_check_out',
        'reason',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'reviewed_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function logActivity(string $action, array $context = []): void
    {
        Log::notice('hr.attendance_correction.'.$action, array_merge($context, [
            'tenant_id' => $this->tenant_id,
            'center_id' => $this->center_id,
            'employee_id' => $this->employee_id,
            'correction_request_id' => $this->id,
            'user_id' => auth()->id(),
        ]));
    }
}
