<?php

namespace App\Listeners\WorkOrder;

use App\Events\WorkOrder\WorkOrderStatusChanged;

class LogActivityOnStatusChange
{
    public function handle(WorkOrderStatusChanged $event): void
    {
        if (method_exists($event->workOrder, 'logActivity')) {
            $event->workOrder->logActivity('status_changed', __(
                'work_orders.activities.actions.status_changed',
                ['from' => $event->oldStatus, 'to' => $event->newStatus]
            ));
        }
    }
}
