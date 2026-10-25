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
use Illuminate\Support\Facades\DB;

class Customer extends Model
{
    use HasFactory, SoftDeletes, TenantScoped;

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Model $model) {
            if (empty($model->center_id) && $centerId = TenancyContext::centerId()) {
                $model->center_id = $centerId;
            }
        });
    }

    // Type constants
    public const TYPE_INDIVIDUAL = 'individual';

    public const TYPE_COMPANY = 'company';

    public const TYPE_GOVERNMENT = 'government';

    public const TYPES = [
        self::TYPE_INDIVIDUAL,
        self::TYPE_COMPANY,
        self::TYPE_GOVERNMENT,
    ];

    protected $fillable = [
        'tenant_id',
        'center_id',
        'type',
        'name',
        'contact_name',
        'phone',
        'whatsapp',
        'email',
        'notes',
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
        'lat',
        'lng',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function getOutstandingBalance(): float
    {
        $result = DB::table('invoices')
            ->where('customer_id', $this->id)
            ->whereNull('deleted_at')
            ->whereNotIn('status', ['cancelled'])
            ->selectRaw('COALESCE(SUM(total_incl_tax - total_paid), 0) as total_due')
            ->first();

        return (float) ($result?->total_due ?? 0.0);
    }

    public function isCreditLimitExceeded(?float $additionalAmount = 0.0): bool
    {
        if (! $this->credit_limit || (float) $this->credit_limit <= 0) {
            return false;
        }

        return ($this->getOutstandingBalance() + ($additionalAmount ?? 0)) >= (float) $this->credit_limit;
    }

    public function getAvailableCredit(): ?float
    {
        if (! $this->credit_limit || (float) $this->credit_limit <= 0) {
            return null;
        }

        return max(0.0, (float) $this->credit_limit - $this->getOutstandingBalance());
    }
}
