<?php

namespace App\Policies;

use App\Models\Part;
use App\Models\User;

class PartPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('inventory.view');
    }

    public function view(User $user, Part $part): bool
    {
        return $user->can('inventory.view') 
            && $user->tenant_id === $part->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->can('inventory.moves.create');
    }

    public function update(User $user, Part $part): bool
    {
        return $user->can('inventory.moves.create') 
            && $user->tenant_id === $part->tenant_id;
    }

    public function delete(User $user, Part $part): bool
    {
        return $user->can('inventory.moves.create') 
            && $user->tenant_id === $part->tenant_id;
    }
}

