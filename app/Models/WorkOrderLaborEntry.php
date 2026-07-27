<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\CenterScoped;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrderLaborEntry extends Model
{
    use CenterScoped, HasFactory;

    public const STATUS_RUNNING = 'running';

    public const STATUS_PAUSED = 'paused';

    public const STATUS_COMPLETED = 'completed';

    public const STATUSES = [
        self::STATUS_RUNNING,
        self::STATUS_PAUSED,
        self::STATUS_COMPLETED,
    ];

    protected $fillable = [
        'tenant_id', 'center_id', 'work_order_id', 'work_order_item_id', 'user_id',
        'started_at', 'resumed_at', 'paused_at', 'ended_at',
        'paused_duration_seconds', 'status', 'pause_reason', 'notes',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'resumed_at' => 'datetime',
        'paused_at' => 'datetime',
        'ended_at' => 'datetime',
        'paused_duration_seconds' => 'integer',
    ];

    // ==================== Relationships ====================

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function workOrderItem(): BelongsTo
    {
        return $this->belongsTo(WorkOrderItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    // ==================== Scopes ====================

    public function scopeRunning(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_RUNNING);
    }

    public function scopeOpen(Builder $query): Builder
    {
        // Open = anything not yet completed (running or paused).
        return $query->whereIn('status', [self::STATUS_RUNNING, self::STATUS_PAUSED]);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    // ==================== State helpers ====================

    public function isRunning(): bool
    {
        return $this->status === self::STATUS_RUNNING;
    }

    public function isPaused(): bool
    {
        return $this->status === self::STATUS_PAUSED;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isOpen(): bool
    {
        return $this->isRunning() || $this->isPaused();
    }

    // ==================== Duration math ====================

    /**
     * Effective elapsed work time in seconds, excluding paused
     * intervals. Live for running/paused sessions, frozen for
     * completed ones.
     *
     * Math:
     *   end  = ended_at  ?? now()
     *   work = end - started_at - paused_duration_seconds
     *   if currently paused, also subtract (now - paused_at) — the
     *   most recent pause hasn't been accumulated yet.
     */
    public function effectiveDurationSeconds(): int
    {
        if (! $this->started_at) {
            return 0;
        }

        $end = $this->ended_at ?? Carbon::now();
        $total = $end->getTimestamp() - $this->started_at->getTimestamp();
        $total -= (int) $this->paused_duration_seconds;

        if ($this->isPaused() && $this->paused_at) {
            $total -= Carbon::now()->getTimestamp() - $this->paused_at->getTimestamp();
        }

        return max(0, $total);
    }

    public function effectiveDurationSecondsAt(Carbon $moment): int
    {
        if (! $this->started_at) {
            return 0;
        }

        $effectiveEnd = match (true) {
            $this->ended_at !== null => $this->ended_at,
            $this->paused_at !== null => $this->paused_at, // paused — clock stopped
            default => $moment,
        };

        $total = $effectiveEnd->getTimestamp() - $this->started_at->getTimestamp();
        $total -= (int) $this->paused_duration_seconds;

        return max(0, $total);
    }
}
