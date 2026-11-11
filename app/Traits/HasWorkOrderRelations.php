<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Center;
use App\Models\Customer;
use App\Models\Dealer;
use App\Models\Department;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PurchaseInvoice;
use App\Models\Quote;
use App\Models\Tenant;
use App\Models\Vehicle;
use App\Models\WorkOrderActivity;
use App\Models\WorkOrderAttachment;
use App\Models\WorkOrderDamageMark;
use App\Models\WorkOrderInspection;
use App\Models\WorkOrderItem;
use App\Models\WorkOrderItemNote;
use App\Models\WorkOrderItemPart;
use App\Models\WorkOrderLaborEntry;
use App\Models\WorkOrderPhoto;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

trait HasWorkOrderRelations
{
    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return BelongsTo<Center, $this> */
    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return BelongsTo<Dealer, $this> */
    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class);
    }

    /** @return BelongsTo<Vehicle, $this> */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /** @return BelongsTo<Quote, $this> */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class, 'work_order_id')->where('type', 'invoice');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'work_order_id');
    }

    public function creditNotes(): HasMany
    {
        return $this->hasMany(Invoice::class, 'work_order_id')->where('type', 'credit_note');
    }

    public function items(): HasMany
    {
        return $this->hasMany(WorkOrderItem::class);
    }

    public function laborEntries(): HasMany
    {
        return $this->hasMany(WorkOrderLaborEntry::class);
    }

    public function damageMarks(): HasMany
    {
        return $this->hasMany(WorkOrderDamageMark::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(WorkOrderPhoto::class);
    }

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'work_order_departments')
            ->withTimestamps();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function parts(): HasMany
    {
        return $this->hasMany(WorkOrderItemPart::class);
    }

    public function purchaseInvoices(): HasMany
    {
        return $this->hasMany(PurchaseInvoice::class, 'work_order_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(WorkOrderAttachment::class)->orderByDesc('created_at');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(WorkOrderActivity::class)->orderByDesc('created_at');
    }

    public function inspections(): HasMany
    {
        return $this->hasMany(WorkOrderInspection::class)->orderByDesc('performed_at');
    }

    public function generalNotes(): HasMany
    {
        return $this->hasMany(WorkOrderItemNote::class, 'work_order_id');
    }
}
