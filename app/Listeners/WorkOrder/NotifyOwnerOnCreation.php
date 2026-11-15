<?php

declare(strict_types=1);

namespace App\Listeners\WorkOrder;

use App\Events\WorkOrder\WorkOrderCreated;
use App\Services\NotificationService;

class NotifyOwnerOnCreation
{
    public function handle(WorkOrderCreated $event): void
    {
        $workOrder = $event->workOrder;
        $workOrder->loadMissing('customer');

        $code = (string) ($workOrder->code ?? $workOrder->id);
        $customerName = $workOrder->customer?->name;

        $body = $customerName
            ? __('notifications.work_order_created_body_customer', ['customer' => $customerName])
            : __('notifications.work_order_created_body');

        NotificationService::notifyTenantUsers(
            tenantId: $workOrder->tenant_id,
            type: 'work_order.created',
            title: __('notifications.work_order_created_title', ['code' => $code]),
            body: $body,
            actionUrl: '/app/work-orders/'.$workOrder->id,
            actorId: $workOrder->created_by ?? auth()->id(),
            centerId: $workOrder->center_id,
        );
    }
}
