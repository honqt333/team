<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Part;
use App\Models\User;
use App\Support\Permissions;

class PartPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::INVENTORY_VIEW);
    }

    public function view(User $user, Part $part): bool
    {
        return $user->can(Permissions::INVENTORY_VIEW)
            && $user->tenant_id === $part->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->can(Permissions::INVENTORY_MOVES_CREATE);
    }

    public function update(User $user, Part $part): bool
    {
        return $user->can(Permissions::INVENTORY_MOVES_CREATE)
            && $user->tenant_id === $part->tenant_id;
    }

    public function delete(User $user, Part $part): bool
    {
        return $user->can(Permissions::INVENTORY_MOVES_CREATE)
            && $user->tenant_id === $part->tenant_id;
    }
}
