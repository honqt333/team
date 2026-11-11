<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyTransaction extends Model
{
    use SoftDeletes, TenantScoped;

    protected $fillable = [
        'tenant_id',
        'center_id',
        'title',
        'transaction_date',
        'transaction_type',
        'income_category_id',
        'amount',
        'is_taxable',
        'is_vat_inclusive',
        'tax_amount',
        'total_amount',
        'contact_type',
        'contact_id',
        'notes',
        'attachment_path',
        'status',
        'invoice_id',
        'purchase_invoice_id',
        'approved_by',
        'approved_at',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_taxable' => 'boolean',
        'is_vat_inclusive' => 'boolean',
        'transaction_date' => 'date:Y-m-d',
        'approved_at' => 'date:Y-m-d',
        'amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    protected $appends = ['contact', 'attachment_url', 'tax_period'];

    /** @return BelongsTo<Center, $this> */
    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function incomeCategory(): BelongsTo
    {
        return $this->belongsTo(IncomeCategory::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function purchaseInvoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class);
    }

    public function getContactAttribute()
    {
        if ($this->contact_type === 'customer' && $this->contact_id) {
            return Customer::withoutGlobalScope('center_scoped')->where('tenant_id', $this->tenant_id)->find($this->contact_id);
        }

        if ($this->contact_type === 'supplier' && $this->contact_id) {
            return Supplier::withoutGlobalScope('center_scoped')->where('tenant_id', $this->tenant_id)->find($this->contact_id);
        }

        return null;
    }

    public function getAttachmentUrlAttribute(): ?string
    {
        return $this->attachment_path ? asset('storage/'.$this->attachment_path) : null;
    }

    public function getTaxPeriodAttribute(): ?array
    {
        $taxPeriodTransaction = TaxPeriodTransaction::withoutGlobalScopes()
            ->with('taxPeriod:id,name,start_date,end_date')
            ->where('tenant_id', $this->tenant_id)
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->where('transaction_type', 'company_transaction')
                        ->where('transaction_id', $this->id);
                });

                if ($this->invoice_id) {
                    $query->orWhere(function ($q) {
                        $q->where('transaction_type', 'sales_invoice')
                            ->where('transaction_id', $this->invoice_id);
                    });
                }

                if ($this->purchase_invoice_id) {
                    $query->orWhere(function ($q) {
                        $q->where('transaction_type', 'purchase_invoice')
                            ->where('transaction_id', $this->purchase_invoice_id);
                    });
                }
            })
            ->first();

        if (! $taxPeriodTransaction || ! $taxPeriodTransaction->taxPeriod) {
            return null;
        }

        $tp = $taxPeriodTransaction->taxPeriod;

        return [
            'id' => $tp->id,
            'name' => $tp->period_name ?? $tp->name ?? ('#'.$tp->id),
        ];
    }
}
