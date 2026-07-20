<?php

namespace App\Models;

use App\Enums\PaymentType;
use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use HasFactory, SoftDeletes, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'center_id',
        'invoice_id',
        'purchase_invoice_id',
        'work_order_id',
        'amount',
        'payment_date',
        'payment_method',
        'reference',
        'notes',
        'received_by',
        'type', // 'payment' or 'refund'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'datetime',
        'type' => PaymentType::class,
    ];

    protected static function booted(): void
    {
        // Side effects are now dispatched via PaymentObserver:
        //   created()  → PaymentRecorded event
        //                → LinkPaymentToInvoice listener (auto-link invoice)
        //                → UpdateInvoiceStatusOnPayment listener (recalc invoice status)
        //                → HandleAutoInvoiceOnDoneWorkOrder listener (auto-create invoice if WO done)
        //   updated()  → PaymentRecorded event
        //   deleted()  → PaymentRecorded event (recalc invoice status)
    }

    // The maybeAutoCreateInvoiceForDoneWorkOrder method has been moved to
    // App\Listeners\Payment\HandleAutoInvoiceOnDoneWorkOrder for proper
    // event-driven architecture.

    // ─────────────────────────────────────────────────────────────
    // Constants
    // ─────────────────────────────────────────────────────────────

    public const TYPE_PAYMENT = 'payment';

    public const TYPE_REFUND = 'refund';

    public const TYPE_BAD_DEBT = 'bad_debt';

    public const TYPES = [
        self::TYPE_PAYMENT,
        self::TYPE_REFUND,
        self::TYPE_BAD_DEBT,
    ];

    public const METHODS = [
        'cash',
        'mada',
        'visa',
        'mastercard',
        'transfer',
        'apple_pay',
        'stc_pay',
        'tabby',
        'tamara',
        'credit',
        'other',
    ];

    // ─────────────────────────────────────────────────────────────
    // Relationships
    // ─────────────────────────────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function purchaseInvoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class);
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    // ─────────────────────────────────────────────────────────────
    // Scopes
    // ─────────────────────────────────────────────────────────────

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeForCenter($query, int $centerId)
    {
        return $query->where('center_id', $centerId);
    }

    public function scopeForInvoice($query, int $invoiceId)
    {
        return $query->where('invoice_id', $invoiceId);
    }

    public function scopeForWorkOrder($query, int $workOrderId)
    {
        return $query->where('work_order_id', $workOrderId);
    }

    // ─────────────────────────────────────────────────────────────
    // Accessors
    // ─────────────────────────────────────────────────────────────

    public function getPaymentMethodLabelAttribute(): string
    {
        return match ($this->payment_method) {
            'cash' => __('payments.methods.cash'),
            'mada' => __('payments.methods.mada'),
            'visa' => __('payments.methods.visa'),
            'mastercard' => __('payments.methods.mastercard'),
            'transfer' => __('payments.methods.transfer'),
            'apple_pay' => __('payments.methods.apple_pay'),
            'stc_pay' => __('payments.methods.stc_pay'),
            'tabby' => __('payments.methods.tabby'),
            'tamara' => __('payments.methods.tamara'),
            'credit' => __('payments.methods.credit'),
            'other' => __('payments.methods.other'),
            // payment_method can be null on a freshly created
            // payment record that hasn't been finalised yet.
            null, '' => '',
            default => (string) $this->payment_method,
        };
    }
}
