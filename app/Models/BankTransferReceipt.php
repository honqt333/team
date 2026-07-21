<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Billing\Subscription;
use App\Models\Billing\SubscriptionPayment;
use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankTransferReceipt extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'subscription_id',
        'subscription_payment_id',
        'transfer_amount',
        'transfer_reference',
        'transfer_date',
        'bank_name',
        'sender_account_name',
        'sender_account_number',
        'receipt_path',
        'receipt_filename',
        'receipt_mime_type',
        'receipt_size_bytes',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    protected function casts(): array
    {
        return [
            'transfer_amount' => 'decimal:2',
            'transfer_date' => 'date',
            'receipt_size_bytes' => 'integer',
            'reviewed_at' => 'datetime',
        ];
    }

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPayment::class, 'subscription_payment_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'قيد المراجعة',
            self::STATUS_APPROVED => 'موافق عليه',
            self::STATUS_REJECTED => 'مرفوض',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'amber',
            self::STATUS_APPROVED => 'emerald',
            self::STATUS_REJECTED => 'rose',
            default => 'slate',
        };
    }

    public function getReceiptUrlAttribute(): string
    {
        if (! $this->receipt_path) {
            return '';
        }

        return asset('storage/'.$this->receipt_path);
    }
}
