<?php

declare(strict_types=1);

namespace App\Models\Billing;

use App\Models\Concerns\TenantScoped;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionInvoice extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'subscription_id',
        'tenant_id',
        'invoice_number',
        'subtotal',
        'vat',
        'discount',
        'total',
        'status',
        'issued_at',
        'due_at',
        'paid_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'vat' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'issued_at' => 'datetime',
            'due_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class, 'subscription_invoice_id');
    }

    public function installments(): HasMany
    {
        return $this->hasMany(Installment::class);
    }

    /**
     * Check if paid
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Check if overdue
     */
    public function isOverdue(): bool
    {
        return $this->status === 'pending' && $this->due_at?->isPast();
    }

    /**
     * Generate invoice number
     */
    public static function generateNumber(): string
    {
        $year = date('Y');
        $count = self::whereYear('created_at', $year)->count() + 1;

        return "INV-{$year}-".str_pad((string) $count, 6, '0', STR_PAD_LEFT);
    }
}
