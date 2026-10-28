<?php

declare(strict_types=1);

namespace App\Models\Billing;

use App\Models\Concerns\TenantScoped;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromoCodeUsage extends Model
{
    use HasFactory, TenantScoped;

    /**
     * The columns the table really has.
     *
     * `discount_type` and `discount_value` were part of the original
     * migration, but the table was recreated later (the subscription
     * tables were rebuilt to repair a broken foreign key) and those two
     * columns were not carried over. They were listed here all the same,
     * which is why writing a usage failed with "no such column".
     */
    protected $fillable = [
        'promo_code_id',
        'tenant_id',
        'subscription_id',
        'discount_amount',
        'used_at',
    ];

    protected function casts(): array
    {
        return [
            'discount_amount' => 'decimal:2',
            'used_at' => 'datetime',
        ];
    }

    public function promoCode(): BelongsTo
    {
        return $this->belongsTo(PromoCode::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}
