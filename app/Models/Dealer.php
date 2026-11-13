<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use App\Support\TenancyContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dealer extends Model
{
    use HasFactory, SoftDeletes, TenantScoped;

    public const TYPE_INSURANCE = 'insurance';

    public const TYPE_FLEET = 'fleet';

    public const TYPE_RENTAL = 'rental';

    public const TYPE_CORPORATE = 'corporate';

    public const TYPES = [
        self::TYPE_INSURANCE,
        self::TYPE_FLEET,
        self::TYPE_RENTAL,
        self::TYPE_CORPORATE,
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Model $model) {
            if (empty($model->center_id) && $centerId = TenancyContext::centerId()) {
                $model->center_id = $centerId;
            }
        });
    }

    protected $fillable = [
        'tenant_id',
        'center_id',
        'name',
        'name_en',
        'type',
        'contact_name',
        'phone',
        'whatsapp',
        'email',
        'tax_number',
        'credit_limit',
        'payment_terms_days',
        'address_line',
        'building_number',
        'postal_code',
        'district',
        'city',
        'region',
        'country',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'payment_terms_days' => 'integer',
        'is_active' => 'boolean',
    ];

    protected $appends = ['display_name'];

    public function getDisplayNameAttribute(): string
    {
        $locale = app()->getLocale();

        return (string) ($locale === 'en' ? ($this->name_en ?: $this->name) : ($this->name ?: $this->name_en));
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return BelongsTo<Center, $this> */
    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'dealer_id');
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class, 'dealer_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'dealer_id');
    }

    public function paymentTerms(): HasMany
    {
        return $this->hasMany(DealerPaymentTerm::class, 'dealer_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch($query, ?string $search)
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('name_en', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('contact_name', 'like', "%{$search}%")
                ->orWhere('tax_number', 'like', "%{$search}%");
        });
    }

    /**
     * Calculate outstanding balance on invoices and open uninvoiced work orders billed/linked to this dealer.
     */
    public function getOutstandingBalance(): float
    {
        $invoicedDue = (float) $this->invoices()
            ->withoutGlobalScope('center_scoped')
            ->whereNotIn('status', ['cancelled'])
            ->where(fn ($q) => $q->whereNull('type')->orWhere('type', '!=', 'credit_note'))
            ->toBase()
            ->selectRaw('COALESCE(SUM(CASE WHEN (total_incl_tax - COALESCE(total_paid, 0)) > 0 THEN (total_incl_tax - COALESCE(total_paid, 0)) ELSE 0 END), 0) as total_balance')
            ->value('total_balance');

        $uninvoicedWorkOrders = $this->workOrders()
            ->withoutGlobalScope('center_scoped')
            ->whereNotIn('status', ['cancelled'])
            ->whereDoesntHave('invoice', function ($q) {
                $q->withoutGlobalScope('center_scoped')
                    ->whereNotIn('status', ['cancelled']);
            })
            ->with(['payments', 'creditNotes'])
            ->get();

        $uninvoicedDue = (float) $uninvoicedWorkOrders->sum(fn (WorkOrder $wo) => (float) $wo->balance);

        return max(0.0, round($invoicedDue + $uninvoicedDue, 2));
    }
}
