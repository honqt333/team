<?php

namespace App\Events\WorkOrder;

use App\Models\WorkOrder;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WorkOrderStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public WorkOrder $workOrder,
        public string $oldStatus,
        public string $newStatus,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("work-order.{$this->workOrder->id}"),
            new PrivateChannel("tenant.{$this->workOrder->tenant_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'work-order.status-changed';
    }

    public function broadcastWith(): array
    {
        return [
            'work_order_id' => $this->workOrder->id,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'changed_at' => now()->toIso8601String(),
        ];
    }
}
