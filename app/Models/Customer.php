<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes, TenantScoped;

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Model $model) {
            if (empty($model->center_id) && $centerId = \App\Support\TenancyContext::centerId()) {
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
}
