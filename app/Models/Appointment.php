<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\CenterScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Appointment extends Model
{
    use CenterScoped, HasFactory;

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_CHECKED_IN = 'checked_in';

    public const STATUS_NO_SHOW = 'no_show';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_CONVERTED = 'converted';

    public const STATUSES = [
        self::STATUS_SCHEDULED,
        self::STATUS_CONFIRMED,
        self::STATUS_CHECKED_IN,
        self::STATUS_NO_SHOW,
        self::STATUS_CANCELLED,
        self::STATUS_CONVERTED,
    ];

    /** Statuses that "hold" a slot — i.e. conflict with new bookings. */
    public const BLOCKING_STATUSES = [
        self::STATUS_SCHEDULED,
        self::STATUS_CONFIRMED,
        self::STATUS_CHECKED_IN,
    ];

    public const SOURCE_INTERNAL = 'internal';

    protected $fillable = [
        'tenant_id', 'center_id',
        'customer_id', 'vehicle_id',
        'service_bay_id', 'assigned_user_id',
        'scheduled_at', 'estimated_end_at',
        'status', 'notes', 'source',
        'checked_in_at', 'cancelled_at', 'cancellation_reason',
        'work_order_id',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'estimated_end_at' => 'datetime',
        'checked_in_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    // ==================== Relationships ====================

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function serviceBay(): BelongsTo
    {
        return $this->belongsTo(ServiceBay::class);
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    /**
     * Pivot: services + their per-appointment duration estimate.
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'appointment_service')
            ->withPivot('estimated_minutes')
            ->withTimestamps();
    }

    // ==================== Helpers ====================

    public function isBlocking(): bool
    {
        return in_array($this->status, self::BLOCKING_STATUSES, true);
    }

    public function isConvertible(): bool
    {
        return in_array(
            $this->status,
            [self::STATUS_SCHEDULED, self::STATUS_CONFIRMED, self::STATUS_CHECKED_IN],
            true
        );
    }

    public function isConverted(): bool
    {
        return $this->status === self::STATUS_CONVERTED;
    }

    /**
     * Time-window overlap check: do two ranges [aStart, aEnd] and
     * [bStart, bEnd] touch at all? Standard half-open interval
     * semantics — a booking that ends exactly when the next one
     * starts is NOT a conflict.
     */
    public function overlapsWith(self $other): bool
    {
        return $this->scheduled_at->lt($other->estimated_end_at)
            && $other->scheduled_at->lt($this->estimated_end_at);
    }
}
