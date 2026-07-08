<?php

namespace App\Traits;

use App\Models\Tenant;
use App\Models\Center;
use App\Models\Customer;
use App\Models\Vehicle;
use App\Models\Quote;
use App\Models\Invoice;
use App\Models\WorkOrderItem;
use App\Models\WorkOrderDamageMark;
use App\Models\WorkOrderPhoto;
use App\Models\Department;
use App\Models\Payment;
use App\Models\WorkOrderItemPart;
use App\Models\WorkOrderAttachment;
use App\Models\WorkOrderActivity;
use App\Models\WorkOrderInspection;
use App\Models\WorkOrderItemNote;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

trait HasWorkOrderRelations
{
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(WorkOrderItem::class);
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
