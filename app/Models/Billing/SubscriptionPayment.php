<?php

namespace App\Models\Billing;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'subscription_id',
        'subscription_invoice_id',
        'amount',
        'currency',
        'gateway',
        'gateway_payment_id',
        'gateway_ref',
        'status',
        'payment_method',
        'gateway_response',
        'failure_reason',
        'initiated_at',
        'paid_at',
        'refunded_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'gateway_response' => 'array',
            'initiated_at' => 'datetime',
            'paid_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SubscriptionInvoice::class, 'subscription_invoice_id');
    }

    /**
     * Mark as initiated.
     */
    public function markAsInitiated(string $gatewayPaymentId = null): self
    {
        $this->update([
            'status' => 'initiated',
            'gateway_payment_id' => $gatewayPaymentId,
            'initiated_at' => now(),
        ]);
        return $this;
    }

    /**
     * Mark as paid.
     */
    public function markAsPaid(array $response = []): self
    {
        $this->update([
            'status' => 'paid',
            'gateway_response' => $response,
            'paid_at' => now(),
        ]);
        return $this;
    }

    /**
     * Mark as failed.
     */
    public function markAsFailed(string $reason = null, array $response = []): self
    {
        $this->update([
            'status' => 'failed',
            'failure_reason' => $reason,
            'gateway_response' => $response,
        ]);
        return $this;
    }

    /**
     * Check if payment is successful.
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Get status label in Arabic.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'معلق',
            'initiated' => 'قيد المعالجة',
            'paid' => 'مدفوع',
            'failed' => 'فشل',
            'refunded' => 'مسترجع',
            'cancelled' => 'ملغي',
            default => $this->status,
        };
    }
}
