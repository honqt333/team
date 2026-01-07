<?php

namespace App\Models\Credits;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantWhatsappBalance extends Model
{
    protected $fillable = [
        'tenant_id',
        'balance',
        'total_purchased',
        'total_used',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function hasCredits(int $amount = 1): bool
    {
        return $this->balance >= $amount;
    }

    public function useCredits(int $amount = 1): bool
    {
        if (!$this->hasCredits($amount)) {
            return false;
        }

        $this->decrement('balance', $amount);
        $this->increment('total_used', $amount);
        return true;
    }

    public function addCredits(int $amount): void
    {
        $this->increment('balance', $amount);
        $this->increment('total_purchased', $amount);
    }

    public static function getOrCreate(int $tenantId): self
    {
        return self::firstOrCreate(
            ['tenant_id' => $tenantId],
            ['balance' => 0, 'total_purchased' => 0, 'total_used' => 0]
        );
    }
}
