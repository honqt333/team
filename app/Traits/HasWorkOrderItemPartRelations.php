<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Center;
use App\Models\InventoryMove;
use App\Models\Part;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Models\WorkOrderPurchaseAllocation;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

trait HasWorkOrderItemPartRelations
{
    /** @return BelongsTo<WorkOrder, $this> */
    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    /** @return BelongsTo<WorkOrderItem, $this> */
    public function workOrderItem(): BelongsTo
    {
        return $this->belongsTo(WorkOrderItem::class);
    }

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

    /** @return BelongsTo<Part, $this> */
    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }

    /** @return BelongsTo<Warehouse, $this> */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /** @return BelongsTo<InventoryMove, $this> */
    public function inventoryMove(): BelongsTo
    {
        return $this->belongsTo(InventoryMove::class);
    }

    /** @return BelongsTo<InventoryMove, $this> */
    public function reversalMove(): BelongsTo
    {
        return $this->belongsTo(InventoryMove::class, 'reversal_move_id');
    }

    /** @return BelongsTo<User, $this> */
    public function reversedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    /** @return HasOne<WorkOrderPurchaseAllocation, $this> */
    public function purchaseAllocation(): HasOne
    {
        return $this->hasOne(WorkOrderPurchaseAllocation::class, 'work_order_item_part_id');
    }
}
