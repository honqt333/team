<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

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
    ];

    protected static function booted(): void
    {
        static::saved(function (Payment $payment) {
            // If the payment belongs to a work order and doesn't have invoice_id,
            // but the work order has an invoice, link it!
            if ($payment->work_order_id && !$payment->invoice_id) {
                $invoice = $payment->workOrder?->invoice;
                if ($invoice) {
                    $payment->invoice_id = $invoice->id;
                    $payment->saveQuietly();
                }
            }

            // Update invoice payment status if invoice_id is set
            if ($payment->invoice_id) {
                $payment->invoice?->updatePaymentStatus();
            }

            // Auto-create the invoice when the WO is fully paid and has been
            // closed (status = done) but no invoice was issued at exit time.
            // This handles the flow where the operator exited the vehicle
            // (deferred invoice) and the customer later pays the full
            // balance — we want the invoice to materialise automatically
            // instead of silently sitting in arrears.
            self::maybeAutoCreateInvoiceForDoneWorkOrder($payment);
        });

        static::deleted(function (Payment $payment) {
            // Update invoice payment status if invoice_id was set
            if ($payment->invoice_id) {
                $payment->invoice?->updatePaymentStatus();
            }
        });
    }

    /**
     * When a payment brings a closed (done) WorkOrder to a fully-paid state
     * and no invoice exists yet, create one so the customer gets a receipt
     * without an extra manual step.
     */
    protected static function maybeAutoCreateInvoiceForDoneWorkOrder(Payment $payment): void
    {
        // Only proceed for refunds on a closed WO — payments are the common
        // case; refunds don't change "fully paid" in a way that should
        // trigger a brand new invoice.
        if ($payment->type === self::TYPE_REFUND) {
            return;
        }

        $workOrder = $payment->workOrder;
        if (!$workOrder) {
            return;
        }

        if ($workOrder->invoice) {
            return; // already invoiced
        }

        if ($workOrder->status !== \App\Models\WorkOrder::STATUS_DONE) {
            return; // WO is still active — invoice creation is the operator's job
        }

        // Use a 1-cent tolerance so floating-point rounding doesn't block
        // an invoice that is, in practice, fully paid.
        if ((float) $workOrder->balance > 0.01) {
            return;
        }

        // Avoid re-entrancy: the InvoiceService may itself create payments
        // or trigger events that come back through this observer.
        if (app()->bound('payment.auto-invoice-in-progress')) {
            return;
        }
        app()->instance('payment.auto-invoice-in-progress', true);

        try {
            /** @var \App\Services\InvoiceService $invoiceService */
            $invoiceService = app(\App\Services\InvoiceService::class);
            $invoice = $invoiceService->createFromWorkOrder($workOrder, $payment->receivedBy);
            $invoiceService->issueInvoice($invoice);

            // Re-link this payment + any other payments on the WO to the
            // newly created invoice so the totals stay in sync.
            $workOrder->payments()->whereNull('invoice_id')->update(['invoice_id' => $invoice->id]);
            $invoice->updatePaymentStatus();

            \App\Services\NotificationService::notifyOwner(
                tenantId: $workOrder->tenant_id,
                type: 'invoice.created',
                title: 'فاتورة جديدة #' . $invoice->invoice_number,
                body: 'تم إنشاء فاتورة تلقائياً من أمر العمل #' . ($workOrder->code ?? $workOrder->id) . ' بعد اكتمال الدفع',
                actionUrl: '/app/invoices/' . $invoice->id,
                actorId: $payment->received_by,
            );
        } catch (\Throwable $e) {
            \Log::warning('Auto-create invoice on done WO failed', [
                'work_order_id' => $workOrder->id,
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
        } finally {
            app()->forgetInstance('payment.auto-invoice-in-progress');
        }
    }

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
        return match($this->payment_method) {
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
            default => $this->payment_method,
        };
    }
}
