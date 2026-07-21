<?php

declare(strict_types=1);

namespace App\Listeners\WorkOrder;

use App\Events\WorkOrder\WorkOrderCreated;
use App\Services\NotificationService;

class NotifyOwnerOnCreation
{
    public function handle(WorkOrderCreated $event): void
    {
        if (class_exists(NotificationService::class) && method_exists(NotificationService::class, 'notifyOwner')) {
            NotificationService::notifyOwner(
                tenantId: $event->workOrder->tenant_id,
                type: 'work_order.created',
                title: 'أمر عمل جديد #'.($event->workOrder->code ?? $event->workOrder->id),
                body: 'تم إنشاء أمر عمل جديد',
                actionUrl: '/app/work-orders/'.$event->workOrder->id,
            );
        }
    }
}
