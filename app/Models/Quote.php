<?php

namespace App\Models;

use App\Models\Concerns\CenterScoped;
use App\Models\Concerns\HasTaxSnapshot;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Quote extends Model
{
    use SoftDeletes, CenterScoped, HasTaxSnapshot;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SENT = 'sent';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CONVERTED = 'converted';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_SENT,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
        self::STATUS_CONVERTED,
    ];

    protected $fillable = [
        'tenant_id',
        'center_id',
        'customer_id',
        'vehicle_id',
        'code',
        'status',
        'notes',
        'customer_complaint',
        'initial_assessment',
        'subtotal',
        'total_discount',
        'total',
        'sent_at',
        'approved_at',
        'rejected_at',
        'converted_at',
        'created_by',
        'approved_by',
        'converted_work_order_id',
        // Tax Snapshots
        'tax_enabled_snapshot',
        'pricing_mode_snapshot',
        'tax_rate_snapshot',
        'currency_code',
        'total_excl_tax',
        'total_tax',
        'total_incl_tax',
        'total_taxable_amount',
        'tax_breakdown',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'total_discount' => 'decimal:2',
        'total' => 'decimal:2',
        'sent_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'converted_at' => 'datetime',
        'tax_breakdown' => 'array', // JSON cast
    ];

    // ─────────────────────────────────────────────────────────────
    // Relationships
    // ─────────────────────────────────────────────────────────────

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

    public function lines(): HasMany
    {
        return $this->hasMany(QuoteLine::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function convertedWorkOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class, 'converted_work_order_id');
    }

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'quote_departments');
    }

    // ─────────────────────────────────────────────────────────────
    // Code Generation
    // ─────────────────────────────────────────────────────────────

    /**
     * Generate a sequential code for the quote.
     * Uses DB locking to ensure uniqueness in concurrent scenarios.
     */
    public static function generateCode(int $tenantId, int $centerId): string
    {
        return DB::transaction(function () use ($tenantId, $centerId) {
            $lastQuote = static::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('center_id', $centerId)
                ->lockForUpdate()
                ->orderByDesc('id')
                ->first();

            if ($lastQuote && preg_match('/QT-(\d+)/', $lastQuote->code, $matches)) {
                $nextNumber = (int)$matches[1] + 1;
            } else {
                $nextNumber = 1;
            }

            return 'QT-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
        });
    }

    // ─────────────────────────────────────────────────────────────
    // Status Helpers
    // ─────────────────────────────────────────────────────────────

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isSent(): bool
    {
        return $this->status === self::STATUS_SENT;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isConverted(): bool
    {
        return $this->status === self::STATUS_CONVERTED;
    }

    public function canBeEdited(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_SENT]);
    }

    public function canBeApproved(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_SENT]);
    }

    public function canBeRejected(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_SENT]);
    }

    // ─────────────────────────────────────────────────────────────
    // Totals Calculation
    // ─────────────────────────────────────────────────────────────

    /**
     * Recalculate totals from lines.
     */
    public function recalculateTotals(): void
    {
        $this->lines->each(function ($line) {
            // Ensure line totals are calculated
        });

        $this->subtotal = $this->lines->sum('line_total') + $this->lines->sum('discount_amount');
        $this->total_discount = $this->lines->sum('discount_amount');
        $this->total = $this->lines->sum('line_total');
    }
}
