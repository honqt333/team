<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Center;
use App\Models\CompanyTransaction;
use App\Models\Payment;
use App\Models\PurchaseInvoiceLine;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReturnInvoice;
use App\Models\Supplier;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

trait HasPurchaseInvoiceRelations
{
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseInvoiceLine::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'purchase_invoice_id');
    }

    public function returnInvoices(): HasMany
    {
        return $this->hasMany(PurchaseReturnInvoice::class);
    }

    public function companyTransaction(): HasOne
    {
        return $this->hasOne(CompanyTransaction::class, 'purchase_invoice_id');
    }
}
