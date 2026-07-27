<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\WorkOrderItem;
use App\Services\WorkOrderLaborService;

class WorkOrderItemObserver
{
    public function __construct(private readonly WorkOrderLaborService $labor) {}

    /**
     * When a work order item moves to completed or cancelled, we
     * have to close any still-open labor sessions for it. The user
     * shouldn't be able to keep ticking a timer on a job that's
     * already closed in the parent record.
     */
    public function updated(WorkOrderItem $item): void
    {
        if (! $item->isDirty('status')) {
            return;
        }

        $newStatus = (string) $item->status;

        if (in_array($newStatus, [WorkOrderItem::STATUS_COMPLETED, WorkOrderItem::STATUS_CANCELLED], true)) {
            $this->labor->closeOpenSessionsForItem($item);
        }
    }
}
