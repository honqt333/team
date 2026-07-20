<?php

namespace App\Events\Part;

use App\Models\Part;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StockLow implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Part $part,
        public int $warehouseId,
        public float $currentQty,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("tenant.{$this->part->tenant_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'stock.low';
    }
}
