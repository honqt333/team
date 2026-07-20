<?php

namespace App\Events\WorkOrder;

use App\Models\WorkOrder;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WorkOrderCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public WorkOrder $workOrder) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("tenant.{$this->workOrder->tenant_id}"),
            new PrivateChannel("center.{$this->workOrder->center_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'work-order.created';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->workOrder->id,
            'code' => $this->workOrder->code,
        ];
    }
}
