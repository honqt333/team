<?php

namespace App\Models;

use App\Models\Concerns\CenterScoped;
use App\Models\Concerns\HasTaxSnapshot;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes, CenterScoped, HasTaxSnapshot;

    protected $appends = ['balance', 'is_paid', 'payment_status_label', 'bad_debt'];

    protected $fillable = [
        'tenant_id',
        'center_id',
        'customer_id',
        'work_order_id',
        'invoice_number',
        'issue_date',
        'supply_date',
        'due_date',
        'notes',
        'type', // invoice, credit_note, debit_note
        'subtype', // simplified, standard
        'status', // draft, valid, reported, cancelled
        'payment_status', // unpaid, partial, paid
        
        // Snapshots
        'customer_name_snapshot',
        'customer_vat_snapshot',
        'customer_address_snapshot',
        
        'tax_enabled_snapshot',
        'pricing_mode_snapshot',
        'tax_rate_snapshot',
        'currency_code',
        
        // Totals
        'total_excl_tax',
        'total_tax',
        'total_incl_tax',
        'total_taxable_amount',
        'total_paid',
        'tax_breakdown',
        
        // ZATCA
        'zatca_qr_tlv',
        'zatca_uuid',
        'zatca_hash',
        'zatca_prev_hash',
        'xml_path',
    ];

    protected $casts = [
        'issue_date' => 'datetime',
        'supply_date' => 'date',
        'due_date' => 'date',
        'tax_enabled_snapshot' => 'boolean',
        'tax_rate_snapshot' => 'decimal:2',
        'total_excl_tax' => 'decimal:2',
        'total_tax' => 'decimal:2',
        'total_incl_tax' => 'decimal:2',
        'total_taxable_amount' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'tax_breakdown' => 'array',
    ];

    // ─────────────────────────────────────────────────────────────
    // Relationships
    // ─────────────────────────────────────────────────────────────

    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }
    
    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function companyTransaction(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(CompanyTransaction::class, 'invoice_id');
    }

    // ─────────────────────────────────────────────────────────────
    // Accessors
    // ─────────────────────────────────────────────────────────────

    public function getBalanceAttribute(): float
    {
        return (float) $this->total_incl_tax - (float) $this->total_paid;
    }

    public function getIsPaidAttribute(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        return match($this->payment_status) {
            'unpaid' => __('invoices.status.unpaid'),
            'partial' => __('invoices.status.partial'),
            'paid' => __('invoices.status.paid'),
            // payment_status can be null on freshly created invoices
            // that have not yet been finalized — render an empty
            // label rather than throwing a TypeError.
            null, '' => '',
            default => (string) $this->payment_status,
        };
    }

    public function getBadDebtAttribute(): float
    {
        if ($this->relationLoaded('payments')) {
            return (float) $this->payments->sum(fn($p) => ($p->type === 'bad_debt' || $p->type === 'Bad_debt') ? $p->amount : 0);
        }
        return (float) $this->payments()->whereIn('type', ['bad_debt', 'Bad_debt'])->sum('amount');
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────

    /**
     * Recalculate payment status based on payments.
     *
     * Refunds are subtracted from the running total so `total_paid` and `balance`
     * stay consistent with what the WorkOrder side already computes
     * (see App\Models\WorkOrder::getTotalPaidAttribute).
     */
    public function updatePaymentStatus(): void
    {
        // Mirror WorkOrder::getTotalPaidAttribute — payments add, refunds subtract.
        // type values come from Payment::TYPES: 'payment' / 'refund' (lowercase).
        $totalPaid = (float) $this->payments()
            ->selectRaw('SUM(CASE WHEN type IN ("payment", "Payment", "bad_debt", "Bad_debt") THEN amount WHEN type IN ("refund", "Refund") THEN -amount ELSE 0 END) as paid')
            ->value('paid');

        $this->total_paid = $totalPaid;

        // Use a 1-cent tolerance so floating-point rounding (e.g. 1000 vs
        // 1000.01 after VAT line aggregation) does not strand an invoice in
        // "partial" forever even though the customer has paid every riyal.
        $totalIncl = (float) $this->total_incl_tax;

        if ($totalIncl <= 0.01) {
            $this->payment_status = 'paid';
        } elseif ($totalPaid <= 0) {
            $this->payment_status = 'unpaid';
        } elseif ($totalPaid >= $totalIncl - 0.01) {
            $this->payment_status = 'paid';
        } else {
            $this->payment_status = 'partial';
        }

        $this->save();
    }

    // ─────────────────────────────────────────────────────────────
    // Scopes
    // ─────────────────────────────────────────────────────────────

    public function scopeUnpaid($query)
    {
        return $query->where('payment_status', 'unpaid');
    }

    public function scopePartiallyPaid($query)
    {
        return $query->where('payment_status', 'partial');
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    public function scopeWithBalance($query)
    {
        return $query->whereIn('payment_status', ['unpaid', 'partial']);
    }

    /**
     * Retrieve the model for a bound value.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        return $this->withoutGlobalScope('center_scoped')
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->where('tenant_id', \App\Support\TenancyContext::tenantId())
            ->first();
    }
}
