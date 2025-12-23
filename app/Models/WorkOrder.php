<?php

namespace App\Models;

use App\Models\Concerns\CenterScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class WorkOrder extends Model
{
    use SoftDeletes, CenterScoped;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_OPEN = 'open';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_DONE = 'done';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_OPEN,
        self::STATUS_IN_PROGRESS,
        self::STATUS_DONE,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'tenant_id',
        'center_id',
        'customer_id',
        'vehicle_id',
        'quote_id',
        'code',
        'status',
        'opened_at',
        'closed_at',
        'notes',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(WorkOrderItem::class);
    }

    /**
     * Generate a sequential code for the work order.
     * Uses DB locking to ensure uniqueness in concurrent scenarios.
     */
    public static function generateCode(int $tenantId, int $centerId): string
    {
        return DB::transaction(function () use ($tenantId, $centerId) {
            // Lock the table row to prevent race conditions
            $lastOrder = static::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('center_id', $centerId)
                ->lockForUpdate()
                ->orderByDesc('id')
                ->first();

            if ($lastOrder && preg_match('/WO-(\d+)/', $lastOrder->code, $matches)) {
                $nextNumber = (int)$matches[1] + 1;
            } else {
                $nextNumber = 1;
            }

            return 'WO-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Calculate total amount of all items.
     */
    public function getTotalAttribute(): float
    {
        return $this->items->sum('total');
    }
}
