<?php

namespace App\Traits;

use App\Models\InvoiceLine;
use App\Models\Payment;
use App\Models\WorkOrder;
use App\Models\Center;
use App\Models\Customer;
use App\Models\Tenant;
use App\Models\CompanyTransaction;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

trait HasInvoiceRelations
{
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

    public function companyTransaction(): HasOne
    {
        return $this->hasOne(CompanyTransaction::class, 'invoice_id');
    }
}
