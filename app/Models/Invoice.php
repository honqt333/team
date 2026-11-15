<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InvoiceType;
use App\Enums\PaymentType;
use App\Models\Concerns\CenterScoped;
use App\Models\Concerns\HasTaxSnapshot;
use App\Models\Concerns\TenantScoped;
use App\Support\TenancyContext;
use App\Traits\HasInvoiceRelations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

/**
 * @property float|null $debt_offset
 * @property float|null $net_refund_due
 */
class Invoice extends Model
{
    use CenterScoped, HasFactory, HasInvoiceRelations, HasTaxSnapshot, SoftDeletes, TenantScoped;

    protected $appends = ['balance', 'is_paid', 'payment_status_label', 'bad_debt'];

    protected $fillable = [
        'tenant_id',
        'center_id',
        'customer_id',
        'dealer_id',
        'is_paid_by_dealer',
        'work_order_id',
        'original_invoice_id',
        'invoice_number',
        'issue_date',
        'supply_date',
        'due_date',
        'notes',
        'return_reason',
        'type', // invoice, credit_note, debit_note
        'subtype', // simplified, standard

        // Snapshots
        'customer_name_snapshot',
        'customer_vat_snapshot',
        'customer_address_snapshot',
        'seller_name_snapshot',
        'seller_name_en_snapshot',
        'seller_vat_snapshot',
        'seller_cr_snapshot',

        'tax_enabled_snapshot',
        'pricing_mode_snapshot',
        'tax_rate_snapshot',
        'currency_code',

        // ZATCA
        'zatca_qr_tlv',
        'zatca_uuid',
        'zatca_hash',
        'zatca_prev_hash',
        'xml_path',
    ];

    protected $casts = [
        'is_paid_by_dealer' => 'boolean',
        'issue_date' => 'datetime',
        'supply_date' => 'date',
        'due_date' => 'date',
        'type' => InvoiceType::class,
        'tax_enabled_snapshot' => 'boolean',
        'tax_rate_snapshot' => 'decimal:2',
        'total_excl_tax' => 'decimal:2',
        'total_tax' => 'decimal:2',
        'total_incl_tax' => 'decimal:2',
        'total_taxable_amount' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'tax_breakdown' => 'array',
    ];

    /**
     * Allow setting initial lifecycle statuses and financial totals only when creating a new record,
     * while preventing mass-assignment mutation of status, payment_status, or financial totals on existing invoices.
     */
    public function fill(array $attributes)
    {
        if (! $this->exists) {
            foreach ([
                'status',
                'payment_status',
                'total_excl_tax',
                'total_tax',
                'total_incl_tax',
                'total_taxable_amount',
                'total_paid',
                'tax_breakdown',
            ] as $protectedKey) {
                if (array_key_exists($protectedKey, $attributes)) {
                    $this->setAttribute($protectedKey, $attributes[$protectedKey]);
                    unset($attributes[$protectedKey]);
                }
            }
        }

        return parent::fill($attributes);
    }

    /**
     * Freeze the seller identity when the invoice row is first created.
     * Reprints then use the identity that was in force at issuance even
     * if the tenant later changes its legal name or VAT number.
     */
    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice): void {
            if (! $invoice->tenant_id) {
                return;
            }

            // Credit notes inherit the issuer on the original document, including absent numbers.
            if ($invoice->seller_name_snapshot !== null || $invoice->seller_name_en_snapshot !== null) {
                $invoice->seller_vat_snapshot ??= '';
                $invoice->seller_cr_snapshot ??= '';

                return;
            }

            $tenant = Tenant::with('taxSettings')->find($invoice->tenant_id);

            if (! $tenant) {
                return;
            }

            /*
             * Empty values are frozen as empty strings, not as null.
             * Null is reserved for legacy rows whose issuer identity
             * was never captured, which may still fall back to the
             * live tenant data on reprint. A new invoice issued while
             * the tenant had no VAT/CR must keep that blank identity
             * even after those numbers are added later.
             */
            $invoice->seller_name_snapshot ??= (string) (
                $tenant->legal_name
                ?: ($tenant->legal_name_en ?: ($tenant->trade_name ?: $tenant->name))
            );
            $invoice->seller_name_en_snapshot ??= (string) (
                $tenant->legal_name_en
                ?: ($tenant->legal_name ?: ($tenant->trade_name ?: $tenant->name))
            );
            $invoice->seller_vat_snapshot ??= (string) (
                $tenant->taxSettings?->tax_number ?: $tenant->vat_number
            );
            $invoice->seller_cr_snapshot ??= (string) $tenant->cr_number;
        });
    }

    // ─────────────────────────────────────────────────────────────
    // Relationships
    // ─────────────────────────────────────────────────────────────

    // ─────────────────────────────────────────────────────────────
    // Accessors
    // ─────────────────────────────────────────────────────────────

    public function isCreditNote(): bool
    {
        return $this->type === InvoiceType::CREDIT_NOTE
            || ($this->type instanceof InvoiceType ? $this->type->value : (string) $this->type) === 'credit_note';
    }

    public function validCreditNotesTotal(): float
    {
        if (! $this->exists || $this->isCreditNote()) {
            return 0.0;
        }

        return (float) self::withoutGlobalScopes()
            ->where('tenant_id', $this->tenant_id)
            ->where('center_id', $this->center_id)
            ->where('original_invoice_id', $this->id)
            ->where('type', InvoiceType::CREDIT_NOTE)
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->whereNull('deleted_at')
            ->sum('total_incl_tax');
    }

    public function getBalanceAttribute(): float
    {
        if ($this->isCreditNote()) {
            return max(0.0, round((float) $this->total_incl_tax - (float) $this->total_paid, 2));
        }

        return max(0.0, round((float) $this->total_incl_tax - $this->validCreditNotesTotal() - (float) $this->total_paid, 2));
    }

    public function getIsPaidAttribute(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        return match ($this->payment_status) {
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
            return (float) $this->payments->sum(fn ($p) => ($p->type === 'bad_debt' || $p->type === PaymentType::BAD_DEBT) ? $p->amount : 0);
        }

        return (float) $this->payments()->where('type', 'bad_debt')->sum('amount');
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
        if ($this->isCreditNote()) {
            $totalRefunded = (float) $this->payments()
                ->selectRaw('SUM(CASE WHEN type IN ("refund", "payment") THEN amount WHEN type = "bad_debt" THEN -amount ELSE 0 END) as refunded')
                ->value('refunded');

            $totalIncl = (float) $this->total_incl_tax;

            if ($totalRefunded >= $totalIncl - 0.01) {
                $this->payment_status = 'paid';
                $this->total_paid = $totalIncl;
            } elseif ($totalRefunded > 0.01) {
                $this->payment_status = 'partial';
                $this->total_paid = $totalRefunded;
            } else {
                $this->payment_status = 'unpaid';
                $this->total_paid = 0;
            }

            $this->save();

            return;
        }

        // Mirror WorkOrder::getTotalPaidAttribute — payments add, refunds subtract.
        // type values come from Payment::TYPES: 'payment' / 'refund' / 'bad_debt' (lowercase).
        $totalPaid = (float) $this->payments()
            ->selectRaw('SUM(CASE WHEN type IN ("payment", "bad_debt") THEN amount WHEN type = "refund" THEN -amount ELSE 0 END) as paid')
            ->value('paid');

        $this->total_paid = $totalPaid;

        // Valid credit notes issued against this invoice reduce its net receivable
        // without mutating frozen invoice totals or double-counting total_paid.
        $totalCredited = $this->validCreditNotesTotal();

        // Use a 1-cent tolerance so floating-point rounding (e.g. 1000 vs
        // 1000.01 after VAT line aggregation) does not strand an invoice in
        // "partial" forever even though the customer has paid every riyal.
        $totalIncl = (float) $this->total_incl_tax;
        $netTotalIncl = max(0.0, round($totalIncl - $totalCredited, 2));

        if ($netTotalIncl <= 0.01) {
            $this->payment_status = 'paid';
        } elseif ($totalPaid >= $netTotalIncl - 0.01) {
            $this->payment_status = 'paid';
        } elseif ($totalPaid > 0.01 || $totalCredited > 0.01) {
            $this->payment_status = 'partial';
        } else {
            $this->payment_status = 'unpaid';
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
     * SQL expression for net outstanding balance on issued sales invoices:
     * MAX(0, total_incl_tax - validCreditNotesTotal - total_paid), compatible with MySQL & SQLite.
     */
    public static function outstandingExpressionSql(string $alias = 'invoices'): string
    {
        $netExpr = "({$alias}.total_incl_tax - COALESCE((SELECT SUM(cn.total_incl_tax) FROM invoices cn WHERE cn.original_invoice_id = {$alias}.id AND cn.tenant_id = {$alias}.tenant_id AND cn.center_id = {$alias}.center_id AND cn.type = 'credit_note' AND cn.status NOT IN ('draft', 'cancelled') AND cn.deleted_at IS NULL), 0) - COALESCE({$alias}.total_paid, 0))";

        return "COALESCE(SUM(CASE WHEN {$netExpr} > 0 THEN {$netExpr} ELSE 0 END), 0)";
    }

    /**
     * Correlated subquery summing valid sales-invoice outstanding balances for a customer/dealer column.
     */
    public static function outstandingSubqueryForColumn(string $foreignKey, string $outerIdColumn, string $outerTenantColumn): Builder
    {
        return self::query()
            ->withoutGlobalScopes()
            ->whereColumn("invoices.{$foreignKey}", $outerIdColumn)
            ->whereColumn('invoices.tenant_id', $outerTenantColumn)
            ->where('invoices.type', InvoiceType::INVOICE->value)
            ->whereNotIn('invoices.status', ['draft', 'cancelled'])
            ->whereNull('invoices.deleted_at')
            ->selectRaw(self::outstandingExpressionSql('invoices'));
    }

    /**
     * Sum valid sales-invoice outstanding balances for a specific owner (customer/dealer) across centers.
     */
    public static function outstandingSumForOwner(string $foreignKey, int $ownerId, ?int $tenantId): float
    {
        $query = self::query()
            ->withoutGlobalScopes()
            ->where("invoices.{$foreignKey}", $ownerId)
            ->where('invoices.type', InvoiceType::INVOICE->value)
            ->whereNotIn('invoices.status', ['draft', 'cancelled'])
            ->whereNull('invoices.deleted_at');

        if ($tenantId !== null) {
            $query->where('invoices.tenant_id', $tenantId);
        }

        return (float) ($query->selectRaw(self::outstandingExpressionSql('invoices').' as total_due')->value('total_due') ?? 0.0);
    }

    /**
     * Retrieve the model for a bound value.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        return $this->withoutGlobalScope('center_scoped')
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->where('tenant_id', TenancyContext::tenantId())
            ->first();
    }

    public function logActivity(string $action): void
    {
        Log::notice('invoice.'.$action, ['tenant_id' => $this->tenant_id, 'center_id' => $this->center_id, 'invoice_id' => $this->id, 'user_id' => auth()->id()]);
    }
}
