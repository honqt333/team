<?php

declare(strict_types=1);

namespace App\Events\HR;

use App\Models\HR\Leave;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LeaveApproved implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Leave $leave,
        public int $approverId,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("leave.{$this->leave->id}"),
            new PrivateChannel("tenant.{$this->leave->tenant_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'leave.approved';
    }
}
