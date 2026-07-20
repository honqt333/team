<?php

namespace App\Observers;

use App\Events\WorkOrder\WorkOrderCreated;
use App\Events\WorkOrder\WorkOrderStatusChanged;
use App\Models\WorkOrder;

class WorkOrderObserver
{
    public function created(WorkOrder $workOrder): void
    {
        event(new WorkOrderCreated($workOrder));
    }

    public function updated(WorkOrder $workOrder): void
    {
        if ($workOrder->isDirty('status')) {
            event(new WorkOrderStatusChanged(
                $workOrder,
                (string) $workOrder->getOriginal('status'),
                (string) $workOrder->status,
            ));
        }
    }
}
