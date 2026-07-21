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
        'tax_amount',
        'total_amount',
        'contact_type',
        'contact_id',
        'notes',
        'status',
        'invoice_id',
        'purchase_invoice_id',
        'approved_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_taxable' => 'boolean',
        'transaction_date' => 'date:Y-m-d',
        'amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    protected $appends = ['contact'];

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
            return Customer::withoutGlobalScopes()->find($this->contact_id);
        }

        if ($this->contact_type === 'supplier' && $this->contact_id) {
            return Supplier::withoutGlobalScopes()->find($this->contact_id);
        }

        return null;
    }
}
