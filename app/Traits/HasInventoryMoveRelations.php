<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Center;
use App\Models\InventoryMove;
use App\Models\Part;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

trait HasInventoryMoveRelations
{
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }

    public function postedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function reversedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    public function reversesMove(): BelongsTo
    {
        return $this->belongsTo(InventoryMove::class, 'reverses_move_id');
    }

    public function reversedByMove(): BelongsTo
    {
        return $this->belongsTo(InventoryMove::class, 'reversed_by_move_id');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
