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
use Illuminate\Support\Str;

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
        'odometer',
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
        'uuid',
        'rejection_reason',
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

    public function parts(): HasMany
    {
        return $this->hasMany(QuotePart::class);
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
    // Boot - Auto UUID
    // ─────────────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::creating(function (Quote $quote) {
            if (empty($quote->uuid)) {
                $quote->uuid = (string) Str::uuid();
            }
        });
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
     * Recalculate totals from lines and parts.
     */
    public function recalculateTotals(): void
    {
        // Load relationships if not loaded to ensure all items are summed
        if (!$this->relationLoaded('lines')) $this->load('lines');
        if (!$this->relationLoaded('parts')) $this->load('parts');

        // Services totals
        $servicesPrice = $this->lines->sum(fn($l) => (float)$l->unit_price * (float)$l->qty);
        $servicesDiscount = $this->lines->sum('discount_amount');
        $servicesTax = $this->lines->sum('tax_amount');
        $servicesTotal = $this->lines->sum('line_total'); // This is total_incl_tax usually

        // Parts totals - process all parts
        $allParts = $this->parts;
        $partsPrice = $allParts->sum(fn($p) => (float)$p->unit_price * (float)$p->qty);
        $partsDiscount = $allParts->sum('discount');
        $partsTax = $allParts->sum('tax_amount');
        $partsTotal = $allParts->sum(fn($p) => (float)($p->total_incl_tax ?: $p->total));

        // Update Quote Totals
        $this->subtotal = $servicesPrice + $partsPrice;
        $this->total_discount = $servicesDiscount + $partsDiscount;
        $this->total_tax = $servicesTax + $partsTax;
        
        if ($this->pricing_mode_snapshot === 'inclusive') {
             $this->total_incl_tax = ($servicesTotal ?: ($servicesPrice - $servicesDiscount)) + $partsTotal;
             $this->total_excl_tax = $this->total_incl_tax - $this->total_tax;
        } else {
             $this->total_excl_tax = ($servicesPrice - $servicesDiscount) + ($partsPrice - $partsDiscount);
             $this->total_incl_tax = $this->total_excl_tax + $this->total_tax;
        }

        $this->total = $this->total_incl_tax;
    }
}
