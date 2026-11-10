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

    protected $appends = ['receipt_url'];

    protected $fillable = [
        'tenant_id',
        'subscription_id',
        'subscription_payment_id',
        'transfer_amount',
        'transfer_reference',
        'transfer_date',
        'bank_name',
        'account_name',
        'receipt_path',
        'receipt_filename',
        'receipt_mime_type',
        'receipt_size_bytes',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
        'notes',
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

    /**
     * The system admin who reviewed the receipt.
     *
     * `reviewed_by` holds an `admin_users.id` — the receipt is reviewed
     * from the /system panel, whose actors are AdminUser records, not
     * tenant Users. The column originally carried a `constrained('users')`
     * foreign key, but that FK is not present in the live schema and
     * the relation below resolved the id against the WRONG table: it
     * returned a tenant `User` whose id happened to collide with the
     * admin's id, so the panel displayed a different person's name as
     * the reviewer. The audit trail has to name the real reviewer.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'reviewed_by');
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

        return request()->is('system/*')
            ? route('system.bank-receipts.download', $this)
            : route('app.billing.receipts.download', $this);
    }
}
