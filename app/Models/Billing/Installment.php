<?php

declare(strict_types=1);

namespace App\Models\Billing;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Installment extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'subscription_id',
        'tenant_id',
        'installment_number',
        'amount',
        'due_date',
        'status',
        'paid_date',
        'paid_amount',
        'payment_method',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'due_date' => 'date',
            'paid_date' => 'date',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
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
        return $this->status === 'pending' && $this->due_date->isPast();
    }

    /**
     * Mark as overdue if past due date
     */
    public function checkOverdue(): void
    {
        if ($this->status === 'pending' && $this->due_date->isPast()) {
            $this->update(['status' => 'overdue']);
        }
    }
}
