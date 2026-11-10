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
    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return BelongsTo<Center, $this> */
    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    /** @return BelongsTo<Warehouse, $this> */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /** @return BelongsTo<Part, $this> */
    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }

    /** @return BelongsTo<User, $this> */
    public function postedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    /** @return BelongsTo<User, $this> */
    public function reversedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    /** @return BelongsTo<InventoryMove, $this> */
    public function reversesMove(): BelongsTo
    {
        return $this->belongsTo(InventoryMove::class, 'reverses_move_id');
    }

    /** @return BelongsTo<InventoryMove, $this> */
    public function reversedByMove(): BelongsTo
    {
        return $this->belongsTo(InventoryMove::class, 'reversed_by_move_id');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
