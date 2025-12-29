<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryTransferItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_transfer_id',
        'part_id',
        'qty_requested',
        'qty_sent',
        'qty_received',
        'unit_cost',
        'send_move_id',
        'receive_move_id',
        'notes',
    ];

    protected $casts = [
        'qty_requested' => 'decimal:3',
        'qty_sent' => 'decimal:3',
        'qty_received' => 'decimal:3',
        'unit_cost' => 'decimal:4',
    ];

    // ─────────────────────────────────────────────────────────────
    // Relationships
    // ─────────────────────────────────────────────────────────────

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(InventoryTransfer::class, 'inventory_transfer_id');
    }

    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }

    public function sendMove(): BelongsTo
    {
        return $this->belongsTo(InventoryMove::class, 'send_move_id');
    }

    public function receiveMove(): BelongsTo
    {
        return $this->belongsTo(InventoryMove::class, 'receive_move_id');
    }

    // ─────────────────────────────────────────────────────────────
    // Accessors
    // ─────────────────────────────────────────────────────────────

    public function getTotalCostAttribute(): float
    {
        return (float) bcmul($this->qty_sent, $this->unit_cost, 2);
    }

    public function getShortageAttribute(): float
    {
        return max(0, (float) $this->qty_sent - (float) $this->qty_received);
    }

    // ─────────────────────────────────────────────────────────────
    // State Checks
    // ─────────────────────────────────────────────────────────────

    public function isSent(): bool
    {
        return $this->send_move_id !== null;
    }

    public function isReceived(): bool
    {
        return $this->receive_move_id !== null;
    }

    public function isFullyReceived(): bool
    {
        return (float) $this->qty_received >= (float) $this->qty_sent;
    }
}
