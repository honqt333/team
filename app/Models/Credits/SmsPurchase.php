<?php

namespace App\Models\Credits;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsPurchase extends Model
{
    protected $fillable = [
        'tenant_id',
        'sms_package_id',
        'credits',
        'amount',
        'payment_gateway',
        'payment_reference',
        'status',
        'payment_details',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_details' => 'array',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(SmsPackage::class, 'sms_package_id');
    }

    /**
     * Mark as paid and add credits.
     */
    public function markAsPaid(string $gateway, string $reference, array $details = []): void
    {
        $this->update([
            'status' => 'paid',
            'payment_gateway' => $gateway,
            'payment_reference' => $reference,
            'payment_details' => $details,
        ]);

        // Add credits to tenant balance
        $balance = TenantSmsBalance::getOrCreate($this->tenant_id);
        $balance->addCredits($this->credits);
    }

    /**
     * Get status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'معلق',
            'paid' => 'مدفوع',
            'failed' => 'فشل',
            'refunded' => 'مسترد',
            default => $this->status,
        };
    }
}
